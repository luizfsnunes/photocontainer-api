<?php

namespace PhotoContainer\PhotoContainer\Infrastructure\Email;

use Interop\Queue\ExceptionInterface as PsrException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrQueue;

class SwiftQueueSpool extends \Swift_ConfigurableSpool
{
    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var PsrQueue
     */
    private $queue;

    /**
     * In milliseconds
     * @var int
     */
    const TIMEOUT = 50;

    /**
     * @param PsrContext      $context
     * @param PsrQueue|string $queue
     */
    public function __construct(PsrContext $context, $queue = 'swiftmailer_spool')
    {
        $this->context = $context;

        if (false == $queue instanceof PsrQueue) {
            $queue = $this->context->createQueue($queue);
        }

        $this->queue = $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function queueMessage(\Swift_Mime_Message $message)
    {
        try {
            $message = $this->context->createMessage(serialize($message));

            $this->context->createProducer()->send($this->queue, $message);
        } catch (PsrException $e) {
            throw new \Swift_IoException(sprintf('Unable to send message to message queue.'), null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flushQueue(\Swift_Transport $transport, &$failedRecipients = null)
    {
        try {
            $consumer = $this->context->createConsumer($this->queue);

            $isTransportStarted = false;

            $failedRecipients = (array) $failedRecipients;
            $count = 0;
            $time = time();

            while (true) {
                if ($psrMessage = $consumer->receive(self::TIMEOUT)) {
                    if (false == $isTransportStarted) {
                        $transport->start();
                        $isTransportStarted = true;
                    }

                    $message = unserialize($psrMessage->getBody());

                    $count += $transport->send($message, $failedRecipients);

                    $consumer->acknowledge($psrMessage);
                }

                if ($this->getMessageLimit() && $count >= $this->getMessageLimit()) {
                    break;
                }

                if ($this->getTimeLimit() && (time() - $time) >= $this->getTimeLimit()) {
                    break;
                }
            }

            return $count;
        } catch (\Exception $e) {
            //@TODO Logar o erro
        }
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return true;
    }
}