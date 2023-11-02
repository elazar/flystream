<?php

use Elazar\Flystream\BufferInterface;
use Elazar\Flystream\LoggingCompositeBuffer;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\LogRecord;

beforeEach(function () {
    $this->logger = new Logger(__FILE__);
    $this->logger->pushHandler(new TestHandler());

    $this->nestedBuffer = new class () implements BufferInterface {
        public bool $flushCalled = false;
        public bool $closeCalled = false;
        public function write(string $data)
        {
            return strlen($data);
        }
        public function flush(
            FilesystemOperator $filesystem,
            string $path,
            array $context
        ): void {
            $this->flushCalled = true;
        }
        public function close(): void
        {
            $this->closeCalled = true;
        }
    };

    $this->buffer = new LoggingCompositeBuffer($this->nestedBuffer, $this->logger);
    $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());
});

it('logs writes', function () {
    $result = $this->buffer->write('foo');
    expect($result)->toBe(3);

    $records = $this->logger->popHandler()->getRecords();
    expect($records)
        ->toBeArray()
        ->toHaveCount(2)
        ->toHaveKeys([0, 1])
        ->each
        ->toBeInstanceOf(LogRecord::class);

    expect($records[0]['message'])->toBe('Elazar\Flystream\LoggingCompositeBuffer::write');
    expect($records[0]['context'])
        ->toHaveKeys(['buffer_class', 'buffer_id'])
        ->toMatchArray([
            'data' => 'foo',
            'when' => 'before',
        ]);
    expect($records[0]['level_name'])->toBe('INFO');

    expect($records[1]['message'])->toBe('Elazar\Flystream\LoggingCompositeBuffer::write');
    expect($records[1]['context'])
        ->toHaveKeys(['buffer_class', 'buffer_id'])
        ->toMatchArray([
            'data' => 'foo',
            'when' => 'after',
            'return' => 3,
        ]);
    expect($records[1]['level_name'])->toBe('INFO');
});

it('logs flushes', function () {
    expect($this->nestedBuffer->flushCalled)->toBeFalse();
    $this->buffer->flush(
        $this->filesystem,
        '/foo',
        ['bar' => 'baz']
    );
    expect($this->nestedBuffer->flushCalled)->toBeTrue();

    $records = $this->logger->popHandler()->getRecords();
    expect($records)
        ->toBeArray()
        ->toHaveCount(2)
        ->toHaveKeys([0, 1])
        ->each
        ->toBeInstanceOf(LogRecord::class);

    expect($records[0]['message'])->toBe('Elazar\Flystream\LoggingCompositeBuffer::flush');
    expect($records[0]['context'])
        ->toHaveKeys(['buffer_class', 'buffer_id', 'filesystem_id'])
        ->toMatchArray([
            'filesystem_class' => 'League\Flysystem\Filesystem',
            'path' => '/foo',
            'context' => ['bar' => 'baz'],
            'when' => 'before',
        ]);
    expect($records[0]['level_name'])->toBe('INFO');

    expect($records[1]['message'])->toBe('Elazar\Flystream\LoggingCompositeBuffer::flush');
    expect($records[1]['context'])
        ->toHaveKeys(['buffer_class', 'buffer_id', 'filesystem_id'])
        ->toMatchArray([
            'filesystem_class' => 'League\Flysystem\Filesystem',
            'path' => '/foo',
            'context' => ['bar' => 'baz'],
            'when' => 'after',
        ]);
    expect($records[1]['level_name'])->toBe('INFO');
});

it('logs closes', function () {
    expect($this->nestedBuffer->closeCalled)->toBeFalse();
    $this->buffer->close();
    expect($this->nestedBuffer->closeCalled)->toBeTrue();

    $records = $this->logger->popHandler()->getRecords();
    expect($records)
        ->toBeArray()
        ->toHaveCount(2)
        ->toHaveKeys([0, 1])
        ->each
        ->toBeInstanceOf(LogRecord::class);

    expect($records[0]['message'])->toBe('Elazar\Flystream\LoggingCompositeBuffer::close');
    expect($records[0]['context'])
        ->toHaveKeys(['buffer_class', 'buffer_id'])
        ->toMatchArray([
            'when' => 'before',
        ]);
    expect($records[0]['level_name'])->toBe('INFO');

    expect($records[1]['message'])->toBe('Elazar\Flystream\LoggingCompositeBuffer::close');
    expect($records[1]['context'])
        ->toHaveKeys(['buffer_class', 'buffer_id'])
        ->toMatchArray([
            'when' => 'after',
        ]);
    expect($records[1]['level_name'])->toBe('INFO');
});
