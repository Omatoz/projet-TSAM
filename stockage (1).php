<?php
session_start();
include 'database.php'; 

header('Content-Type: application/json');

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }
    
    if ($action === 'retirer' && isset($_GET['id']) && $_GET['id'] === 'all') {
        $_SESSION['panier'] = [];
        unset($_SESSION['destination_id']); 
        session_write_close();
        echo json_encode(['status' => 'success', 'panier' => []]);
        exit;
    }
    
    if (isset($_GET['id']) && isset($_GET['type'])) {
        $id = intval($_GET['id']);
        $type = $_GET['type'];
        $item = ['id' => $id, 'type' => $type];
        
        if ($action === 'ajouter') {
            if ($type === 'destination') {
                $_SESSION['destination_id'] = $id;
            }
            if (!in_array($item, $_SESSION['panier'])) {
                $_SESSION['panier'][] = $item;
            }
        } elseif ($action === 'retirer') {
            $_SESSION['panier'] = array_filter($_SESSION['panier'], function($v) use ($id, $type) {
                return !($v['id'] == $id && $v['type'] == $type);
            });
            $_SESSION['panier'] = array_values($_SESSION['panier']);
        }
        
        session_write_close();
        echo json_encode(['status' => 'success', 'panier' => $_SESSION['panier']]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Paramètres invalides']);
exit;
?>