<?php
include 'database.php'; 

header('Content-Type: application/json');

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }
    
    // Cas spécial de vidage total initié par la page index.php
    if ($action === 'retirer' && isset($_GET['id']) && $_GET['id'] === 'all') {
        $_SESSION['panier'] = [];
        session_write_close();
        echo json_encode(['status' => 'success', 'panier' => []]);
        exit;
    }
    
    // Cas standards d'ajouts ou retraits brique par brique
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        if ($action === 'ajouter') {
            if (!in_array($id, $_SESSION['panier'])) {
                $_SESSION['panier'][] = $id;
            }
        } elseif ($action === 'retirer') {
            $_SESSION['panier'] = array_diff($_SESSION['panier'], [$id]);
            $_SESSION['panier'] = array_values($_SESSION['panier']);
        }
        
        session_write_close();
        echo json_encode([
            'status' => 'success', 
            'panier' => $_SESSION['panier']
        ]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Paramètres invalides']);
exit;
?>