<?php

namespace Elazar\Flystream;

class FileBuffer extends AbstractBuffer
{
    /**
     * {@inheritdoc}
     */
    protected function createStream(): mixed
    {
        $stream = tmpfile();
        if ($stream === false) {
            // Once the stream is opened, writes to it will succeed even if
            // the file is deleted or has its permissions changed. As such,
            // testing this would require changing the sys_temp_dir setting
            // in php.ini to reference an unwritable directory to prevent
            // the initial write from succeeding.
            //
            // This could be accomplished by invoking relevant test code in
            // a separate process with sys_temp_dir overridden via a -d
            // flag, but that's a lot of trouble to go to just to test this
            // relatively simple block. So, just ignore it for the purposes
            // of code coverage.
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }
        return $stream;
    }
}
