<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

class SocrataAPI {
    private $domain;
    private $dataset;
    private $appToken;
    private $baseUrl;
    
    public function __construct() {
        $this->domain = getenv('DOMAIN_DATOS');
        $this->dataset = getenv('LICITACIONES_DATASET');
        $this->appToken = getenv('CONTRATOS_APP_TOKEN');
        
        if (!$this->domain || !$this->dataset || !$this->appToken) {
            throw new Exception('Configuración de API incompleta. Verifique las variables de entorno.');
        }
        $this->baseUrl = "https://{$this->domain}/resource/{$this->dataset}.json";
    }
    
    public function getLicitaciones() {
        $soql = "SELECT entidad, ciudad_entidad, descripci_n_del_procedimiento, precio_base, duracion " .
                "WHERE fase = 'Presentación de oferta' " .
                "AND estado_del_procedimiento = 'Publicado' " .
                "AND precio_base > 0 " .
                "ORDER BY fecha_de_ultima_publicaci DESC";
        
        $params = [
            '$query' => $soql
        ];
        
        $url = $this->baseUrl . '?' . http_build_query($params);
        
        return $this->makeRequest($url);
    }
    
    private function makeRequest($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'X-App-Token: ' . $this->appToken,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Error cURL: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Error en la API de Socrata: HTTP code $httpCode");
        }
        
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al decodificar la respuesta JSON');
        }
        
        return $decodedResponse;
    }
    
    public function formatResults($results) {
        if (empty($results)) {
            return [];
        }
        
        return [
            'success' => true,
            'data' => $results
        ];
    }
}

try {
    $api = new SocrataAPI();
    $results = $api->getLicitaciones();
    $formattedResults = $api->formatResults($results);
    
    echo json_encode($formattedResults);
    
} catch (Exception $e) {
    error_log('Error en Socrata API: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error en la búsqueda: ' . $e->getMessage()
    ]);
}
?>