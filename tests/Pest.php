<?php

expect()->extend('toTriggerWarning', function (string $expectedWarningMessage) {
    $originalErrorReporting = error_reporting();
    error_reporting(E_ALL); // Ensure warnings are reported

    $warningTriggered = false;
    $actualWarningMessage = '';

    set_error_handler(function ($errno, $errstr) use (&$warningTriggered, &$actualWarningMessage) {
        if ($errno === E_WARNING) {
            $warningTriggered = true;
            $actualWarningMessage = $errstr;
        }
        return true; // Prevent the default error handler from running
    });

    try {
        if (!is_callable($this->value)) {
            throw new \InvalidArgumentException('Value must be a callable');
        }
        $this->value = ($this->value)();
    } catch (\Throwable $e) {
        // Ignore any exceptions, we're only interested in warnings
        $this->value = false;
    }

    restore_error_handler();
    error_reporting($originalErrorReporting);

    expect($warningTriggered)->toBeTrue("Expected warning was not triggered");
    expect($actualWarningMessage)->toContain($expectedWarningMessage);

    return $this; // Return $this for chaining
});
