<?php

declare(strict_types=1);

/**
 * Base model that exposes the shared PDO instance for child repositories.
 */
abstract class BaseModel
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}
