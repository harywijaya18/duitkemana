<?php

class ApiPaymentMethodController extends ApiController
{
    private PaymentMethodModel $paymentMethodModel;

    public function __construct()
    {
        $this->paymentMethodModel = $this->model(PaymentMethodModel::class);
    }

    public function index(): void
    {
        $this->requireApiAuth();
        $rows = $this->paymentMethodModel->all();
        $this->success(['payment_methods' => $rows]);
    }
}
