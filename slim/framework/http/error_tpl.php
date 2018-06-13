<?php $error_id = uniqid('error'); ?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">

    <title><?= htmlspecialchars($title, ENT_SUBSTITUTE, 'UTF-8') ?></title>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="container">
        <h1><?= htmlspecialchars($title, ENT_SUBSTITUTE, 'UTF-8'), ($code ? ' #' . $code : '') ?></h1>
        <p>
            <?= $exception->getMessage() ?>
            <a href="https://www.baidu.com/s?wd=<?= urlencode($title . ' ' . preg_replace('#\'.*\'|".*"#Us', '', $exception->getMessage())) ?>"
               rel="noreferrer" target="_blank">search &rarr;</a>
        </p>
    </div>
</div>

<!-- Source -->
<div class="container">
    <p><b><?= $file ?></b> at line <b><?= $line ?></b></p>

    <?php if (is_file($file)) : ?>
        <div class="source">
            <?= self::highlightFile($file, $line, 15); ?>
        </div>
    <?php endif; ?>
</div>

<div class="container">

    <ul class="tabs" id="tabs">
        <li><a href="#backtrace">Backtrace</a></li>
        <li><a href="#request">Request</a></li>
    </ul>

    <div class="tab-content">

        <!-- Backtrace -->
        <div class="content" id="backtrace">

            <ol class="trace">
                <?php foreach ($trace as $index => $row) : ?>

                    <li>
                        <p>
                            <!-- Trace info -->
                            <?php if (isset($row['file']) && is_file($row['file'])) : ?>
                                <?php
                                if (isset($row['function']) && in_array($row['function'], ['include', 'include_once', 'require', 'require_once'])) {
                                    echo $row['function'] . ' ' . $row['file'];
                                } else {
                                    echo $row['file'];
                                }
                                ?>
                            <?php else : ?>
                                {PHP internal code}
                            <?php endif; ?>

                            <!-- Class/Method -->
                            <?php if (isset($row['class'])) : ?>
                                &nbsp;&nbsp;&mdash;&nbsp;&nbsp;<?= $row['class'] . $row['type'] . $row['function'] ?>
                                <?php if (!empty($row['args'])) : ?>
                                    <?php
                                    $params = null;
                                    // Reflection by name is not available for closure function
                                    if (substr($row['function'], -1) !== '}') {
                                        $mirror = isset($row['class']) ? new \ReflectionMethod($row['class'], $row['function']) : new \ReflectionFunction($row['function']);
                                        $params = $mirror->getParameters();
                                    }
                                    $paramstr = '';
                                    foreach ($params as $name => $param) {
                                        if (empty($paramstr)) {
                                            $paramstr = $param;
                                        } else {
                                            $paramstr .= ',' . $param;
                                        }
                                    }
                                    echo '(' . $paramstr . ')';
                                    ?>

                                <?php else : ?>
                                    ()
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!isset($row['class']) && isset($row['function'])) : ?>
                                &nbsp;&nbsp;&mdash;&nbsp;&nbsp;    <?= $row['function'] ?>()
                            <?php endif; ?>
                        </p>

                        <!-- Source? -->
                        <?php if (isset($row['file']) && is_file($row['file']) && isset($row['class'])) : ?>
                            <div class="source">
                                <?= self::highlightFile($row['file'], $row['line']) ?>
                            </div>
                        <?php endif; ?>
                    </li>

                <?php endforeach; ?>
            </ol>

        </div>

        <!-- Request -->
        <div class="content" id="request">
            <?php $request = $this->api->make('request'); ?>

            <h3>Request</h3>
            <table>
                <tbody>
                <tr>
                    <td style="width: 10em">Path</td>
                    <td><?= $request->pathinfo() ?></td>
                </tr>
                <tr>
                    <td>HTTP Method</td>
                    <td><?= $request->method(true) ?></td>
                </tr>
                <tr>
                    <td>IP Address</td>
                    <td><?= $request->ip() ?></td>
                </tr>
                <tr>
                    <td>Is Secure Request?</td>
                    <td><?= $request->isSsl() ? 'yes' : 'no' ?></td>
                </tr>

                </tbody>
            </table>


            <?php $empty = true; ?>
            <?php foreach (['_GET', '_POST'] as $var) : ?>
                <?php if (empty($GLOBALS[$var]) || !is_array($GLOBALS[$var])) {
                    continue;
                } ?>

                <?php $empty = false; ?>

                <h3>$<?= $var ?></h3>

                <table style="width: 100%">
                    <thead>
                    <tr>
                        <th>Key</th>
                        <th>Value</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($GLOBALS[$var] as $key => $value) : ?>
                        <tr>
                            <td><?= htmlspecialchars($key, ENT_IGNORE, 'UTF-8') ?></td>
                            <td>
                                <?php if (!is_array($value) && !is_object($value)) : ?>
                                    <?= htmlspecialchars($value, ENT_SUBSTITUTE, 'UTF-8') ?>
                                <?php else: ?>
                                    <?= '<pre>' . print_r($value, true) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endforeach ?>

            <?php if ($empty) : ?>

                <div class="alert">
                    No $_GET, $_POST, or $_COOKIE Information to show.
                </div>

            <?php endif; ?>

        </div>

        <!-- Memory -->
        <div class="content" id="memory">

            <table>
                <tbody>
                <tr>
                    <td>Memory Usage</td>
                    <td><?= convert_size(memory_get_usage(true)) ?></td>
                </tr>
                <tr>
                    <td style="width: 12em">Peak Memory Usage:</td>
                    <td><?= convert_size(memory_get_peak_usage(true)) ?></td>
                </tr>
                <tr>
                    <td>Memory Limit:</td>
                    <td><?= ini_get('memory_limit') ?></td>
                </tr>
                </tbody>
            </table>

        </div>

    </div>  <!-- /tab-content -->

</div> <!-- /container -->

<div class="footer">
    <div class="container">

        <p>
            Displayed at <?= date('H:i:sa') ?> &mdash;
            PHP: <?= phpversion() ?> &mdash;
            <?= $this->api->make('config')->get('api.name') ?> : <?= $this->api->getVersion() ?>
        </p>

    </div>
</div>

</body>
</html>
