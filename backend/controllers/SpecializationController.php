<?php
/**
 * Specializations Controller (medical_platform)
 */

namespace Backend\Controllers;

use Backend\Core\AuditLogger;
use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use Backend\Core\Validator;

class SpecializationController
{
    public function index(Request $req): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT specialization_id AS id, specialization_name AS name, description 
            FROM specializations 
            ORDER BY specialization_name ASC
        ");
        $data = $stmt->fetchAll();

        Response::json(['ok' => true, 'data' => $data]);
    }

    public function store(Request $req): void
    {
        $user = Auth::requireRole('admin');
        $body = $req->body();

        $val = new Validator();
        if (!$val->validate($body, [
            'name' => ['required', 'min:3', 'max:100'],
            'description' => ['max:500']
        ])) {
            Response::error(422, $val->getFirstError(), $val->getErrors());
        }

        $pdo = Database::getConnection();
        $check = $pdo->prepare("SELECT specialization_id FROM specializations WHERE LOWER(specialization_name) = :name LIMIT 1");
        $check->execute([':name' => strtolower(trim((string) $body['name']))]);
        if ($check->fetch()) {
            Response::error(409, 'Nama spesialisasi sudah ada.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO specializations (specialization_name, description)
            VALUES (:name, :desc)
        ");
        $stmt->execute([
            ':name' => Validator::sanitize($body['name']),
            ':desc' => Validator::sanitize($body['description'] ?? '')
        ]);
        $id = (int) $pdo->lastInsertId();

        AuditLogger::log($user['user_id'], 'CREATE_SPECIALIZATION', 'specialization', $id, 'success');

        Response::json(['ok' => true, 'id' => $id, 'message' => 'Spesialisasi berhasil ditambahkan.'], 201);
    }

    public function update(Request $req): void
    {
        $user = Auth::requireRole('admin');
        $id = (int) $req->param('id');
        $body = $req->body();

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM specializations WHERE specialization_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $spec = $stmt->fetch();
        if (!$spec) {
            Response::error(404, 'Spesialisasi tidak ditemukan.');
        }

        $name = isset($body['name']) ? Validator::sanitize($body['name']) : $spec['specialization_name'];
        $desc = isset($body['description']) ? Validator::sanitize($body['description']) : $spec['description'];

        $up = $pdo->prepare("UPDATE specializations SET specialization_name = :name, description = :desc WHERE specialization_id = :id");
        $up->execute([':name' => $name, ':desc' => $desc, ':id' => $id]);

        AuditLogger::log($user['user_id'], 'UPDATE_SPECIALIZATION', 'specialization', $id, 'success');

        Response::json(['ok' => true, 'data' => ['id' => $id, 'name' => $name, 'description' => $desc]]);
    }
}
