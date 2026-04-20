<?php
namespace App\Controllers;

use App\Models\Business;
use Exception;

class BusinessController {
    public function mapView() {
        try {
            $negocios = Business::search(['visible' => 1]);
            return $this->render('business/map', ['negocios' => $negocios]);
        } catch (Exception $e) {
            return $this->render('errors/500');
        }
    }

    protected function render($view, $data = []) {
        extract($data);
        include __DIR__ . '/../views/' . $view . '.php';
    }
}
