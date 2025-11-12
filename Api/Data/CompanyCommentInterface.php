<?php

namespace GardenLawn\Company\Api\Data;

interface CompanyCommentInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    const  string comment_id = 'comment_id';
    const  string text = 'text';
    const  string company_id = 'company_id';

    public function getCommentId(): ?int;
    public function setCommentId($commentId);
    public function getText(): string;
    public function setText($text);
    public function getCompanyId(): int;
    public function setCompanyId($companyId);
}
