<?php

namespace CobreFacil\Resources;

use CobreFacil\BaseTest;
use CobreFacil\Exceptions\InvalidParamsException;
use CobreFacil\Exceptions\ResourceNotFoundException;
use DateInterval;
use DateTime;

class InvoiceTest extends BaseTest
{
    public function testCreateBankSlip()
    {
        $params = [
            'payable_with' => Invoice::PAYMENT_METHOD_BANKSLIP,
            'customer_id' => $this->getLastCustomerId(),
            'due_date' => date('Y-m-d'),
            'items' => [
                [
                    'description' => 'Teclado',
                    'quantity' => 1,
                    'price' => 4999,
                ],
                [
                    'description' => 'Mouse',
                    'quantity' => 1,
                    'price' => 3999,
                ],
            ],
            'settings' => [
                'late_fee' => [
                    'mode' => 'percentage',
                    'amount' => 10,
                ],
                'interest' => [
                    'mode' => 'daily_percentage',
                    'amount' => 0.1,
                ],
                'discount' => [
                    'mode' => 'fixed',
                    'amount' => 9.99,
                    'limit_date' => 5,
                ],
                'warning' => [
                    'description' => '- Em caso de dúvidas entre em contato com nossa Central de Atendimento.',
                ],
            ],
        ];
        $response = $this->cobreFacil->invoice()->create($params);
        $this->assertNotNull($response['id']);
        $this->assertEquals(Invoice::STATUS_PROCESSING, $response['status']);
    }

    public function testErrorOnCreateBankSlipWithInvalidDueDate()
    {
        $yesterday = (new DateTime())->sub(new DateInterval('P1D'))->format('Y-m-d');
        $params = [
            'payable_with' => Invoice::PAYMENT_METHOD_BANKSLIP,
            'customer_id' => $this->getLastCustomerId(),
            'due_date' => $yesterday,
            'items' => [
                [
                    'description' => 'Teclado',
                    'quantity' => 1,
                    'price' => 4999,
                ],
                [
                    'description' => 'Mouse',
                    'quantity' => 1,
                    'price' => 3999,
                ],
            ],
            'settings' => [
                'late_fee' => [
                    'mode' => 'percentage',
                    'amount' => 10,
                ],
                'interest' => [
                    'mode' => 'daily_percentage',
                    'amount' => 0.1,
                ],
                'discount' => [
                    'mode' => 'fixed',
                    'amount' => 9.99,
                    'limit_date' => 5,
                ],
                'warning' => [
                    'description' => '- Em caso de dúvidas entre em contato com nossa Central de Atendimento.',
                ],
            ],
        ];
        try {
            $this->cobreFacil->invoice()->create($params);
        } catch (InvalidParamsException $e) {
            $expectedErrors = [
                'Data de vencimento deve ser uma data maior ou igual a hoje.',
            ];
            $this->assertInvalidParamsException($expectedErrors, $e);
        }
    }

    public function testList()
    {
        $response = $this->cobreFacil->invoice()->list();
        $this->assertTrue(isset($response[0]['id']));
    }

    public function testListWithFilter()
    {
        $filter = [
            'email' => $this->getLastInvoice()['customer']['email'],
        ];
        $response = $this->cobreFacil->invoice()->list($filter);
        $this->assertGreaterThanOrEqual(1, count($response));
        foreach ($response as $invoice) {
            $this->assertEquals($filter['email'], $invoice['customer']['email']);
        }
    }

    public function testGetById()
    {
        $id = $this->getLastInvoiceId();
        $response = $this->cobreFacil->invoice()->getById($id);
        $this->assertEquals($id, $response['id']);
    }

    public function testErrorOnGetByInvalidId()
    {
        $id = 'invalid';
        $invoice = $this->cobreFacil->invoice();
        try {
            $invoice->getById('invalid');
        } catch (ResourceNotFoundException $e) {
            $this->assertEquals("v1/invoices/$id", $invoice->getUri());
            $this->assertEquals('Cobrança não encontrada.', $e->getMessage());
        }
    }

    public function testCancelBankSlip()
    {
        $response = $this->cobreFacil->invoice()->delete($this->getLastInvoiceId(['status' => Invoice::STATUS_PENDING]));
        $this->assertEquals(Invoice::STATUS_CANCELED, $response['status']);
    }

    public function testErrorOnCancelBankSlipWithInvalidId()
    {
        $id = 'invalid';
        $invoice = $this->cobreFacil->invoice();
        try {
            $invoice->delete('invalid');
        } catch (ResourceNotFoundException $e) {
            $this->assertEquals("v1/invoices/$id", $invoice->getUri());
            $this->assertEquals('Cobrança não encontrada.', $e->getMessage());
        }
    }

    public function testErrorOnCancelBankSlipAlreadyCanceled()
    {
        try {
            $this->cobreFacil->invoice()->delete($this->getLastInvoiceId(['status' => Invoice::STATUS_CANCELED]));
        } catch (InvalidParamsException $e) {
            $this->assertEquals('Somente faturas pendentes podem ser canceladas.', $e->getMessage());
        }
    }

    protected function getLastInvoiceId(array $filter = null): string
    {
        return $this->getLastInvoice($filter)['id'];
    }

    protected function getLastInvoice(array $filter = null): array
    {
        return $this->cobreFacil->invoice()->list($filter)[0];
    }
}
