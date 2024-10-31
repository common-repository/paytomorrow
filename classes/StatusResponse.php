<?php

class StatusResponse
{
    public $message;
    public $status;
    public $token;
    public $lender;
    public $loanAmount;
    public bool $taxExempt;
    public $maxApprovalAmount;

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message): void
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getLender()
    {
        return $this->lender;
    }

    /**
     * @param mixed $lender
     */
    public function setLender($lender): void
    {
        $this->lender = $lender;
    }

    /**
     * @return mixed
     */
    public function getMaxApprovalAmount()
    {
        return $this->maxApprovalAmount;
    }

    /**
     * @param mixed $maxApprovalAmount
     */
    public function setMaxApprovalAmount($maxApprovalAmount): void
    {
        $this->maxApprovalAmount = $maxApprovalAmount;
    }

    /**
     * @return mixed
     */
    public function getLoanAmount()
    {
        return $this->loanAmount;
    }

    /**
     * @param mixed $loanAmount
     */
    public function setLoanAmount($loanAmount): void
    {
        $this->loanAmount = $loanAmount;
    }

    public function isTaxExempt(): bool
    {
        return $this->taxExempt;
    }

    public function setTaxExempt(bool $taxExempt): void
    {
        $this->taxExempt = $taxExempt;
    }





}