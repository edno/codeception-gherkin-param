<?php
namespace Helper;

class ConfigHelper extends \Codeception\Module
{
    public function setConfigParam(string $param, bool $value): void
    {
        $this->getModule('Codeception\Extension\GherkinParam')
            ->_reconfigure(array($param => $value));
    }
}
