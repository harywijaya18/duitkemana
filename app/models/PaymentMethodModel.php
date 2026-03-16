<?php

class PaymentMethodModel extends Model
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM payment_methods ORDER BY id ASC');
        return $stmt->fetchAll();
    }
}
