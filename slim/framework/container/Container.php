<?php
// +----------------------------------------------------------------------
// | slimAPI [ A slim framework for API ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://www.slimapi.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Jin.oy <503ouyang@sina.com>
// +----------------------------------------------------------------------

namespace slim\container;

use Closure;
use ArrayAccess;
use ReflectionClass;
use ReflectionParameter;
use slim\contracts\container\Container as ContractsContainer;

class Container implements ArrayAccess, ContractsContainer
{
    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static $instance;

    /**
     * An array of the types that have been resolved.
     *
     * @var array
     */
    protected $resolved = [];

    /**
     * The container's bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * The container's method bindings.
     *
     * @var array
     */
    protected $methodBindings = [];

    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected $instances = [];

    /**
     * The stack of concretions currently being built.
     *
     * @var array
     */
    protected $buildStack = [];

    /**
     * The parameter override stack.
     *
     * @var array
     */
    protected $with = [];

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string $abstract
     *
     * @return bool
     */
    public function bound($abstract)
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Register a binding with the container.
     *
     * @param  string               $abstract
     * @param  \Closure|string|null $concrete
     * @param  bool                 $shared
     *
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type. After that, the concrete type to be registered as shared
        // without being forced to state their classes in both of the parameters.
        $this->dropStaleInstances($abstract);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // If the factory is not a Closure, it means it is just a class name which is
        // bound into this container to the abstract type and we will just wrap it
        // up inside its own Closure to give us more convenience when extending.
        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');

        // If the abstract type was already resolved in this container we'll fire the
        // rebound listener so that any objects which have already gotten resolved
        // can have their copy of the object updated via the listener callbacks.
        if ($this->resolved($abstract)) {
            $this->make($abstract);
        }
    }

    /**
     * Determine if the given abstract type has been resolved.
     *
     * @param  string $abstract
     *
     * @return bool
     */
    public function resolved($abstract)
    {
        return isset($this->resolved[$abstract]) ||
               isset($this->instances[$abstract]);
    }

    /**
     * Drop all of the stale instances and aliases.
     *
     * @param  string $abstract
     *
     * @return void
     */
    protected function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Get the Closure to be used when building a type.
     *
     * @param  string $abstract
     * @param  string $concrete
     *
     * @return \Closure
     */
    protected function getClosure($abstract, $concrete)
    {
        return function($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete);
            }

            return $container->make($concrete, $parameters);
        };
    }

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param  string               $abstract
     * @param  \Closure|string|null $concrete
     * @param  bool                 $shared
     *
     * @return void
     */
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Register a shared binding in the container.
     *
     * @param  string               $abstract
     * @param  \Closure|string|null $concrete
     *
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string $abstract
     * @param  mixed  $instance
     *
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        $isBound = $this->bound($abstract);

        // We'll check to determine if this type has been bound before, and if it has
        // we will fire the rebound callbacks registered with the container and it
        // can be updated with consuming classes that have gotten resolved here.
        $this->instances[$abstract] = $instance;

        if ($isBound) {
            $this->make($abstract);
        }


        return $instance;
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string $abstract
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string $abstract
     * @param  array  $parameters
     *
     * @return mixed
     */
    protected function resolve($abstract, $parameters = [])
    {
        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $this->with[] = $parameters;

        $concrete = $this->getConcrete($abstract);

        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete);
        } else {
            $object = $this->make($concrete);
        }

        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        // Before returning, we will also set the resolved flag to "true" and pop off
        // the parameter overrides for this build. After those two things are done
        // we will be ready to return back the fully constructed class instance.
        $this->resolved[$abstract] = true;

        array_pop($this->with);

        return $object;
    }

    /**
     * Determine if a given type is shared.
     *
     * @param  string $abstract
     *
     * @return bool
     */
    public function isShared($abstract)
    {
        return isset($this->instances[$abstract]) ||
               (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param  string $abstract
     *
     * @return mixed   $concrete
     */
    protected function getConcrete($abstract)
    {
        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }


    /**
     * Determine if the given concrete is buildable.
     *
     * @param  mixed  $concrete
     * @param  string $abstract
     *
     * @return bool
     */
    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }


    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param  string $concrete
     *
     * @return mixed
     */
    public function build($concrete)
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        $reflector = new ReflectionClass($concrete);

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface of Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if (!$reflector->isInstantiable()) {
            return $this->notInstantiable($concrete);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (is_null($constructor)) {
            array_pop($this->buildStack);

            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        $instances = $this->resolveDependencies(
            $dependencies
        );

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }


    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param  array $dependencies
     *
     * @return array
     */
    protected function resolveDependencies(array $dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If this dependency has a override for this particular build we will use
            // that instead as the value. Otherwise, we will continue with this run
            // of resolutions and let reflection attempt to determine the result.
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);

                continue;
            }

            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
            $results[] = is_null($dependency->getClass())
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);
        }

        return $results;
    }

    /**
     * Resolve a class based dependency from the container.
     *
     * @param  \ReflectionParameter $parameter
     *
     * @return mixed
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->make($parameter->getClass()->name);
        }

            // If we can not resolve the class instance, we will check to see if the value
            // is optional, and if it is we will return the optional parameter value as
            // the value of the dependency, similarly to how we do this with scalars.
        catch (\Exception $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }

            throw $e;
        }
    }

    /**
     * Resolve a non-class hinted primitive dependency.
     *
     * @param  \ReflectionParameter $parameter
     *
     * @return mixed
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        $this->unresolvablePrimitive($parameter);
    }

    /**
     * Throw an exception for an unresolvable primitive.
     *
     * @param  \ReflectionParameter $parameter
     *
     * @return void
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter)
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new \Exception($message);
    }


    /**
     * Determine if the given dependency has a parameter override.
     *
     * @param  \ReflectionParameter $dependency
     *
     * @return bool
     */
    protected function hasParameterOverride($dependency)
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }

    /**
     * Get a parameter override for a dependency.
     *
     * @param  \ReflectionParameter $dependency
     *
     * @return mixed
     */
    protected function getParameterOverride($dependency)
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    /**
     * Get the last parameter override.
     *
     * @return array
     */
    protected function getLastParameterOverride()
    {
        return count($this->with) ? end($this->with) : [];
    }


    /**
     * Throw an exception that the concrete is not instantiable.
     *
     * @param  string $concrete
     *
     * @return void
     */
    protected function notInstantiable($concrete)
    {
        if (!empty($this->buildStack)) {
            $previous = implode(', ', $this->buildStack);

            $message = "Target [$concrete] is not instantiable while building [$previous].";
        } else {
            $message = "Target [$concrete] is not instantiable.";
        }

        throw new \Exception($message);
    }


    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string $callback
     * @param  array           $parameters
     * @param  string|null     $defaultMethod
     *
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        return BoundMethod::call($this, $callback, $parameters, $defaultMethod);
    }

    /**
     * Determine if the container has a method binding.
     *
     * @param  string $method
     *
     * @return bool
     */
    public function hasMethodBinding($method)
    {
        return isset($this->methodBindings[$method]);
    }

    /**
     * Bind a callback to resolve with Container::call.
     *
     * @param  string   $method
     * @param  \Closure $callback
     *
     * @return void
     */
    public function bindMethod($method, $callback)
    {
        $this->methodBindings[$method] = $callback;
    }

    /**
     * Get the method binding for the given method.
     *
     * @param  string $method
     * @param  mixed  $instance
     *
     * @return mixed
     */
    public function callMethodBinding($method, $instance)
    {
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }


    /**
     * Set the shared instance of the container.
     *
     * @param  \slim\Contracts\Container\Container|null $container
     *
     * @return static
     */
    public static function setInstance(ContractsContainer $container = null)
    {
        return static::$instance = $container;
    }

    /**
     * Determine if a given offset exists.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->bound($key);
    }

    /**
     * Get the value at a given offset.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->bind($key, $value instanceof Closure ? $value : function() use ($value) {
            return $value;
        });
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
    }

    /**
     * Dynamically access container services.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }

}