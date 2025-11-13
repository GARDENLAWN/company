<?php

namespace GardenLawn\Company\Model;

use DateTime;
use GardenLawn\Company\Api\Data\CompanyCommentInterface;
use Magento\Framework\Model\AbstractModel;

class CompanyComment extends AbstractModel implements CompanyCommentInterface
{
    protected function _construct(): void
    {
        $this->_init('GardenLawn\Company\Model\ResourceModel\CompanyComment');
    }

    public function getCommentId(): ?int
    {
        return $this->getData(self::comment_id);
    }

    public function setCommentId($commentId): void
    {
        $this->setData(self::comment_id, $commentId);
    }

    public function getText(): string
    {
        return $this->getData(self::text);
    }

    public function setText($text): void
    {
        $this->setData(self::text, $text);
    }

    public function getCompanyId(): int
    {
        return $this->getData(self::company_id);
    }

    public function setCompanyId($companyId): void
    {
        $this->setData(self::company_id, $companyId);
    }
}
