<?php
namespace App\Core;

class Controller {
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit();
    }
    
    protected function success($message = '', $data = []) {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->jsonResponse($response);
    }
    
    protected function error($message = '', $statusCode = 400) {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->jsonResponse($response, $statusCode);
    }
    
    protected function getInput() {
        return json_decode(file_get_contents('php://input'), true);
    }
    
    protected function handleOptions() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}
?>