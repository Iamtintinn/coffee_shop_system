<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'add':
        $product_id = (int)($_POST['product_id'] ?? 0);
        $size = $_POST['size'] ?? 'Medium';
        $temperature = $_POST['temperature'] ?? 'Iced';
        $sugar_level = $_POST['sugar_level'] ?? '50%';
        $ice_level = $_POST['ice_level'] ?? 'Regular Ice';
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $instructions = $_POST['instructions'] ?? '';
        $addon_ids = $_POST['addons'] ?? [];

        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? AND status = 'available'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        $addons = [];
        if (!empty($addon_ids)) {
            $placeholders = implode(',', array_fill(0, count($addon_ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM add_ons WHERE addon_id IN ($placeholders) AND status = 'active'");
            $stmt->execute($addon_ids);
            $addons = $stmt->fetchAll();
        }

        $cart = $_SESSION['pos_cart'] ?? [];

        $cart_key = $product_id . '_' . md5($size . $temperature . $sugar_level . $ice_level . implode(',', $addon_ids) . $instructions);

        if (isset($cart[$cart_key])) {
            $cart[$cart_key]['qty'] += $qty;
        } else {
            $cart[$cart_key] = [
                'product_id'   => $product_id,
                'name'         => $product['name'],
                'price'        => (float)$product['price'],
                'size'         => $size,
                'temperature'  => $temperature,
                'sugar_level'  => $sugar_level,
                'ice_level'    => $ice_level,
                'qty'          => $qty,
                'instructions' => $instructions,
                'addons'       => [],
            ];
            foreach ($addons as $a) {
                $cart[$cart_key]['addons'][] = [
                    'id'    => $a['addon_id'],
                    'name'  => $a['name'],
                    'price' => (float)$a['price'],
                ];
            }
        }

        $_SESSION['pos_cart'] = $cart;
        echo json_encode(['success' => true, 'message' => 'Added to cart']);
        break;

    case 'remove':
        $key = $_POST['key'] ?? '';
        $cart = $_SESSION['pos_cart'] ?? [];
        if (isset($cart[$key])) {
            unset($cart[$key]);
            $_SESSION['pos_cart'] = $cart;
        }
        echo json_encode(['success' => true]);
        break;

    case 'qty':
        $key = $_POST['key'] ?? '';
        $value = (int)($_POST['value'] ?? 0);
        $cart = $_SESSION['pos_cart'] ?? [];
        if (isset($cart[$key])) {
            $cart[$key]['qty'] = max(1, $cart[$key]['qty'] + $value);
            if ($cart[$key]['qty'] < 1) unset($cart[$key]);
            $_SESSION['pos_cart'] = $cart;
        }
        echo json_encode(['success' => true]);
        break;

    case 'edit':
        $key = $_POST['key'] ?? '';
        $cart = $_SESSION['pos_cart'] ?? [];
        if (isset($cart[$key])) {
            $item = $cart[$key];
            echo json_encode([
                'success' => true,
                'item' => $item,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
        break;

    case 'clear':
        $_SESSION['pos_cart'] = [];
        echo json_encode(['success' => true]);
        break;

    case 'hold':
        $cart = $_SESSION['pos_cart'] ?? [];
        if (empty($cart)) {
            echo json_encode(['success' => false, 'message' => 'Cart is empty']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO held_orders (order_data, customer_name, held_by) VALUES (?, ?, ?)");
        $stmt->execute([json_encode($cart), 'Walk-in Customer', $_SESSION['user_id']]);
        $_SESSION['pos_cart'] = [];
        echo json_encode(['success' => true, 'message' => 'Order held']);
        break;

    case 'refresh':
    default:
        $cart = $_SESSION['pos_cart'] ?? [];
        $count = 0;
        $subtotal = 0;
        $html = '';

        if (empty($cart)) {
            $html = '<div class="cart-empty"><i class="bi bi-bag-x"></i> Cart is empty</div>';
        } else {
            foreach ($cart as $key => $item) {
                $addon_total = array_sum(array_column($item['addons'] ?? [], 'price'));
                $line_total = ($item['price'] + $addon_total) * $item['qty'];
                $count += $item['qty'];
                $subtotal += $line_total;

                $html .= '<div class="cart-item" data-key="' . $key . '">';
                $html .= '<button class="ci-remove" onclick="cartAction(\'remove\',\'' . $key . '\')">&times;</button>';
                $html .= '<div class="ci-header">';
                $html .= '<span class="ci-name">' . htmlspecialchars($item['name']) . '</span>';
                $html .= '<span class="ci-price">₱' . number_format($line_total, 2) . '</span>';
                $html .= '</div>';
                $html .= '<div class="ci-opts">';
                if (!empty($item['size'])) $html .= '<span>' . htmlspecialchars($item['size']) . '</span>';
                if (!empty($item['temperature'])) $html .= '<span>' . htmlspecialchars($item['temperature']) . '</span>';
                if (!empty($item['sugar_level'])) $html .= '<span>Sugar: ' . htmlspecialchars($item['sugar_level']) . '</span>';
                if (!empty($item['ice_level'])) $html .= '<span>Ice: ' . htmlspecialchars($item['ice_level']) . '</span>';
                foreach ($item['addons'] ?? [] as $a) {
                    $html .= '<span>+' . htmlspecialchars($a['name']) . '</span>';
                }
                if (!empty($item['instructions'])) {
                    $html .= '<br><em style="font-size:10px;">' . htmlspecialchars($item['instructions']) . '</em>';
                }
                $html .= '</div>';
                $html .= '<div class="ci-actions">';
                $html .= '<button onclick="cartAction(\'qty\',\'' . $key . '\',-1)">−</button>';
                $html .= '<span class="ci-qty">' . $item['qty'] . '</span>';
                $html .= '<button onclick="cartAction(\'qty\',\'' . $key . '\',1)">+</button>';
                $html .= '<button onclick="cartAction(\'edit\',\'' . $key . '\')" style="margin-left:auto;width:auto;padding:0 8px;font-size:11px;">Edit</button>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        echo json_encode([
            'success'  => true,
            'cart_html' => $html,
            'count'    => $count,
            'subtotal' => $subtotal,
            'total'    => $subtotal,
        ]);
        break;
}
