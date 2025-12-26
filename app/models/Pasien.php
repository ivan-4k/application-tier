<?php
namespace App\Models;

use App\Core\Model;

class Pasien extends Model {
    protected $table = 'pasien';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->insert($data);
    }
}
?>