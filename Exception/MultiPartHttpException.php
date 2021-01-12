<?php

namespace Wizards\RestBundle\Exception;

/**
 * Multiple messages in a single http exception.
 */
class MultiPartHttpException extends \WizardsRest\Exception\HttpException
{
    /**
     * @var string[]
     */
    private $messageList;

    public function __construct(int $statusCode = 500, array $messageList = ['Internal Server Error.'])
    {
        $this->messageList = $messageList;

        parent::__construct($statusCode, 'Multi-Part Error');
    }

    public function getMessageList(): array
    {
        return $this->messageList;
    }
}
