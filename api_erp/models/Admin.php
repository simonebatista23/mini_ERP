<?php

require_once __DIR__ . '/../database/base.php';

class Admin
{

    private $conn;

    public function __construct()
    {
        $this->conn = Database::conectar();
    }

    public function buscarEmailAdmin($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM  admins where email = ? ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
