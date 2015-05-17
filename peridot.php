<?php

use Peridot\Core\Behavior\Listener;
use Peridot\EventEmitterInterface;
use Peridot\Cli\Environment;
use Peridot\Reporter\ReporterInterface;
use Symfony\Component\Console\Input\InputOption;

return function(EventEmitterInterface $emitter) {
    $listener = new Listener($emitter);
    $listener->listen();

    $counts = ['pass' => 0, 'fail' => 0, 'pending' => 0];

    $emitter->on('test.failed', function() use (&$counts) {
        $counts['fail']++;
    });

    $emitter->on('test.passed', function() use (&$counts) {
        $counts['pass']++;
    });

    $emitter->on('test.pending', function() use (&$counts) {
        $counts['pending']++;
    });

    $codeCoverage = getenv('CODE_COVERAGE');
    $hhvm = defined('HHVM_VERSION'); //exclude coverage from hhvm because its pretty flawed at the moment
    $php7 = preg_match('/^7/', PHP_VERSION);
    $shouldCover = !$hhvm && $php7 == 0;

    if ($codeCoverage == 'html' && $shouldCover) {
        $coverage = new PHP_CodeCoverage();
        $emitter->on('runner.start', function() use ($coverage) {
            $coverage->filter()->addFileToBlacklist(__DIR__. '/src/Dsl.php');
            $coverage->start('peridot');
        });

        $emitter->on('runner.end', function() use ($coverage) {
            $coverage->stop();
            $writer = new PHP_CodeCoverage_Report_HTML();
            $writer->process($coverage, __DIR__ . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'report');
        });
    }

    if ($codeCoverage == 'clover' && $shouldCover) {
        $coverage = new PHP_CodeCoverage();
        $emitter->on('runner.start', function() use ($coverage) {
            $coverage->filter()->addFileToBlacklist(__DIR__. '/src/Dsl.php');
            $coverage->start('peridot');
        });

        $emitter->on('runner.end', function() use ($coverage) {
            $coverage->stop();
            $writer = new PHP_CodeCoverage_Report_Clover();
            $writer->process(
                $coverage, __DIR__ . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR
                . 'logs' . DIRECTORY_SEPARATOR . 'clover.xml');
        });
    }

    $emitter->on('peridot.start', function(Environment $env) use (&$coverage) {
        $definition = $env->getDefinition();
        $definition->option("banner", null, InputOption::VALUE_REQUIRED, "Custom banner text");
    });

    /**
     * Demonstrate registering a custom reporter via peridot config
     */
    $emitter->on('peridot.reporters', function($input, $reporters) use (&$counts) {
        $banner = $input->getOption('banner');
        $reporters->register('basic', 'a simple summary', function(ReporterInterface $reporter) use (&$counts, $banner) {
            $output = $reporter->getOutput();

            $reporter->getEventEmitter()->on('runner.start', function() use ($banner, $output) {
                $output->writeln($banner);
            });

            $reporter->getEventEmitter()->on('runner.end', function() use ($output, &$counts) {
                $output->writeln(sprintf(
                    '%d run, %d failed, %d pending',
                    $counts['pass'],
                    $counts['fail'],
                    $counts['pending']
                ));
            });
        });
    });
};
