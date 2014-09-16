<?php
use Peridot\Core\SpecResult;
use Peridot\Core\Suite;
use Peridot\Test\ItWasRun;

describe("Suite", function() {
    it("should run multiple specs", function () {
        $suite = new Suite("Suite");
        $suite->addSpec(new ItWasRun("should pass", function () {}));
        $suite->addSpec(new ItWasRun('should fail', function () {
            throw new \Exception('woooooo!');
        }));

        $result = new SpecResult();
        $suite->run($result);
        assert('2 run, 1 failed' == $result->getSummary(), "result summary should have show 2/1");
    });
});
