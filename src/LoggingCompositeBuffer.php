<?php

namespace Elazar\Flystream;

use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class LoggingCompositeBuffer implements BufferInterface
{
    private BufferInterface $buffer;

    private LoggerInterface $logger;

    public function __construct(
        BufferInterface $buffer,
        LoggerInterface $logger
    ) {
        $this->buffer = $buffer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $data)
    {
        $context = $this->getContext([
            'data' => $data,
        ]);

        $context['when'] = 'before';
        $this->logger->info(__METHOD__, $context);

        $return = $context['return'] = $this->buffer->write($data);

        $context['when'] = 'after';
        $this->logger->info(__METHOD__, $context);

        return $return;
    }

    public function flush(
        FilesystemOperator $filesystem,
        string $path,
        array $context
    ): void {
        $context = $this->getContext([
            'filesystem_class' => get_class($filesystem),
            'filesystem_id' => spl_object_id($filesystem),
            'path' => $path,
            'context' => $context,
        ]);

        $context['when'] = 'before';
        $this->logger->info(__METHOD__, $context);

        $this->buffer->flush($filesystem, $path, $context);

        $context['when'] = 'after';
        $this->logger->info(__METHOD__, $context);
    }

    public function close(): void
    {
        $context = $this->getContext();

        $context['when'] = 'before';
        $this->logger->info(__METHOD__, $context);

        $this->buffer->close();

        $context['when'] = 'after';
        $this->logger->info(__METHOD__, $context);
    }

    private function getContext(array $add = []): array
    {
        $default = [
            'buffer_class' => get_class($this->buffer),
            'buffer_id' => spl_object_id($this->buffer),
        ];
        return array_merge($default, $add);
    }
}
