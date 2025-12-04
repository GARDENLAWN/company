<?php

namespace GardenLawn\Company\Controller\Index;

use GardenLawn\Company\Api\Data\CeidgService;
use GardenLawn\Company\Api\Data\Exception\CeidgApiException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class Ceidg extends Action
{
    private JsonFactory $resultJsonFactory;
    private CeidgService $ceidgService;

    public function __construct(
        JsonFactory $resultJsonFactory,
        Context     $context,
        CeidgService $ceidgService
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->ceidgService = $ceidgService;
    }

    /**
     * @throws CeidgApiException
     */
    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $resultJson = $this->resultJsonFactory->create();

        $data = $this->ceidgService->getDataByNip($this->getRequest()->getParam('taxvat'));

        return $resultJson->setData($data);
    }
}
