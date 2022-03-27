<?php

declare(strict_types=1);

namespace Codeception\Extension;

if (version_compare(\Codeception\Codecept::VERSION, "5", "ge")) {
    trait GherkinParamAttributesTrait{

        /**
        * Array of configuration parameters
        *
        * @var array<string>
        */
        protected array $_config = ['onErrorThrowException', 'onErrorNull'];

        /**
        * List events to listen to
        *
        * @var array<string,string>
        */
        public static array $events = [
        //run before any suite
        'suite.before' => 'beforeSuite',
        //run before any steps
        'step.before' => 'beforeStep'
        ];

        /**
        * Current test suite config
        *
        * @var array<mixed>
        */
        private static array $_suiteConfig;
    }
} else {
    trait GherkinParamAttributesTrait{

        /**
        * Array of configuration parameters
        *
        * @var array<string>
        */
        protected $_config = ['onErrorThrowException', 'onErrorNull'];

        /**
        * List events to listen to
        *
        * @var array<string,string>
        */
        public static $events = [
        //run before any suite
        'suite.before' => 'beforeSuite',
        //run before any steps
        'step.before' => 'beforeStep'
        ];

        /**
        * Current test suite config
        *
        * @var array<mixed>
        */
        private static $_suiteConfig;
    }
}
