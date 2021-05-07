<?php declare(strict_types=1);


namespace App\AdminModule\classes;


class QRPaymentData
{

	/** @var int */
	public int $accountNumber;
	/** @var int */
	public int $bankCode;
	/** @var int */
	public int $amount;
	/** @var int */
	public int $variableSymbol;
	/** @var string */
	public string $message;

	function __construct(int $accountNumber, int $bankCode, int $amount, int $variableSymbol, string $message)
	{
		$this->accountNumber = $accountNumber;
		$this->bankCode = $bankCode;
		$this->amount = $amount;
		$this->variableSymbol = $variableSymbol;
		$this->message = $message;
	}
}
