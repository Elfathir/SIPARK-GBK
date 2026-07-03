<?php

require_once __DIR__ . '/../config/database.php';

/*DATABASE CONNECTION*/
function db()
{
    global $conn;
    return $conn;
}

/*SELECT ALL*/
function getAll($table, $orderBy = null)
{
    $db = db();
    $sql = "SELECT * FROM {$table}";
    if ($orderBy != null) {
        $sql .= " ORDER BY {$orderBy}";
    }
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

/*SELECT ONE*/
function getById($table, $field, $value)
{
    $db = db();
    $stmt = $db->prepare("SELECT * FROM {$table} WHERE {$field} = ?");
    $stmt->execute([$value]);
    return $stmt->fetch();
}

/*DELETE*/
function deleteById($table, $field, $value)
{
    $db = db();
    $stmt = $db->prepare("DELETE FROM {$table} WHERE {$field} = ?");
    return $stmt->execute([$value]);
}

/*COUNT DATA*/
function countData($table)
{
    $db = db();
    $stmt = $db->query("SELECT COUNT(*) FROM {$table}");
    return $stmt->fetchColumn();
}

/*TOTAL BIAYA*/
function totalPendapatan()
{
    $db = db();
    $stmt = $db->query("
        SELECT COALESCE(SUM(total_biaya),0)
        FROM transaksi_parkir
    ");
    return $stmt->fetchColumn();
}