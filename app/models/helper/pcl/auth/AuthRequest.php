<?php 

namespace app\models\helper\pcl\auth;

class AuthRequest 
{
    public $transaction = null;
    public $amount = null;
    public $currency = null;
    public $payment = array();
    public $cheque = array();
    public $authentication = null;	
}