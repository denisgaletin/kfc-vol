<?php

namespace app\models\helper\pcl\refund;

class RefundRequest 
{
    public $transaction = null;
    public $amount = null;
    public $currency = null;
    public $payment = array();
    public $cheque = array();
    public $origId = null;
    public $origTerminal = null;
    public $origLocation = null;
    public $origPartnerId = null;
}