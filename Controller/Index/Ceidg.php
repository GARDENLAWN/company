<?php

namespace GardenLawn\Company\Controller\Index;

use GardenLawn\Company\Api\Data\CeidgService;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class Ceidg extends Action
{
    private Http $request;
    private JsonFactory $resultJsonFactory;
    private CeidgService $ceidgService;

    public function __construct(
        JsonFactory $resultJsonFactory,
        Context     $context,
        Http        $request,
        CeidgService $ceidgService
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->ceidgService = $ceidgService;
    }

    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $resultJson = $this->resultJsonFactory->create();

        $data = $this->ceidgService->getDataByNip($this->request->getParam('nip'));

        return $resultJson->setData($data);
    }
}
