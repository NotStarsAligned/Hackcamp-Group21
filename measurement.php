<?php
// File: measurement.php


// 包含必要的文件
require_once 'Model/TileCalculator.php';
require_once 'Model/Auth.php'; // 包含 Authentication 类
require_once 'Views/measurement.phtml';

/**
 * 瓷砖计算控制器
 * Tile Calculation Controller
 *
 * 这个控制器处理瓷砖计算相关的请求
 * This controller handles tile calculation requests
 */
//
Authentication::requireLogin();
class TileController
{
    /**
     * 处理计算请求
     * Handle calculation request
     *
     * @param array $data 包含房间尺寸和瓷砖尺寸的数据
     * @return array 计算结果
     */
    public function calculate($data)
    {
        try {
            // 验证必需参数
            $this->validateInput($data);

            // 提取并清理数据
            $roomLength = floatval($data['room_length'] ?? 0);
            $roomWidth = floatval($data['room_width'] ?? 0);
            $tileLength = intval($data['tile_length'] ?? 0);
            $tileWidth = intval($data['tile_width'] ?? 0);

            // 处理单位转换（如果需要）
            $unit = $data['unit'] ?? 'metres';
            if ($unit === 'feet') {
                // 英尺转米 (1 foot = 0.3048 metres)
                $roomLength *= 0.3048;
                $roomWidth *= 0.3048;
            }

            // 实例化计算器
            $calculator = new TileCalculator($roomLength, $roomWidth, $tileLength, $tileWidth);

            // 获取计算结果
            $result = $calculator->getSummary();

            // 添加额外信息
            $result['status'] = 'success';
            $result['input_data'] = [
                'room_length' => $roomLength,
                'room_width' => $roomWidth,
                'tile_length' => $tileLength,
                'tile_width' => $tileWidth,
                'unit' => $unit
            ];

            return $result;

        } catch (Exception $e) {
            // 错误处理
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }

    /**
     * 处理来自web表单的POST请求
     * Handle POST request from web form
     *
     * @return void 直接输出JSON响应
     */
    public function handleFormPost()
    {
        // 检查是否为POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('Invalid request method. Use POST.');
            return;
        }

        // 获取POST数据
        $postData = $_POST;

        // 进行计算
        $result = $this->calculate($postData);

        // 输出JSON结果
        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * 处理来自命令行的手动数据计算
     * Handle manual data calculation from command line
     *
     * @param array $args 命令行参数
     * @return void
     */
    public function handleCommandLine($args = null)
    {
        if (php_sapi_name() === 'cli') {
            // 从命令行参数获取数据
            $data = $this->parseCommandLineArgs($args);
        } else {
            // 从GET参数获取数据（用于测试）
            $data = $_GET;
        }

        $result = $this->calculate($data);

        // 格式化输出
        if (php_sapi_name() === 'cli') {
            $this->displayCliResult($result);
        } else {
            header('Content-Type: application/json');
            echo json_encode($result, JSON_PRETTY_PRINT);
        }
    }

    /**
     * 验证输入数据
     * Validate input data
     *
     * @param array $data
     * @throws InvalidArgumentException
     */
    private function validateInput($data)
    {
        $required = ['room_length', 'room_width', 'tile_length', 'tile_width'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new InvalidArgumentException(
                    "Missing required field: {$field}",
                    400
                );
            }

            if (!is_numeric($data[$field])) {
                throw new InvalidArgumentException(
                    "Field {$field} must be numeric",
                    400
                );
            }

            $value = floatval($data[$field]);
            if ($value <= 0) {
                throw new InvalidArgumentException(
                    "Field {$field} must be greater than 0",
                    400
                );
            }
        }
    }

    /**
     * 解析命令行参数
     * Parse command line arguments
     *
     * @param array $args
     * @return array
     */
    private function parseCommandLineArgs($args)
    {
        $data = [];

        // 如果没有提供参数，显示使用说明
        if (empty($args) || count($args) < 5) {
            echo "Usage: php TileController.php [room_length] [room_width] [tile_length] [tile_width] [unit]\n";
            echo "Example: php TileController.php 5 4 600 600 metres\n";
            exit(1);
        }

        $data['room_length'] = $args[1] ?? 0;
        $data['room_width'] = $args[2] ?? 0;
        $data['tile_length'] = $args[3] ?? 0;
        $data['tile_width'] = $args[4] ?? 0;
        $data['unit'] = $args[5] ?? 'metres';

        return $data;
    }

    /**
     * 在命令行中显示结果
     * Display result in command line
     *
     * @param array $result
     */
    private function displayCliResult($result)
    {
        if ($result['status'] === 'error') {
            echo "Error: {$result['message']}\n";
            return;
        }

        echo "=== Tile Calculation Results ===\n\n";

        echo "Room Information:\n";
        echo "  Length: {$result['room_length_m']} m\n";
        echo "  Width: {$result['room_width_m']} m\n";
        echo "  Area: {$result['room_area_m2']} m²\n\n";

        echo "Tile Information:\n";
        echo "  Tile Size: {$result['tile_length_mm']} x {$result['tile_width_mm']} mm\n";
        echo "  Tile Area: {$result['tile_area_m2']} m²\n\n";

        echo "Quantities:\n";
        echo "  Tiles needed (raw): {$result['tiles_needed_raw']}\n";
        echo "  Tiles needed (with 10% waste): {$result['tiles_needed_with_waste']}\n";
        echo "  Boxes needed (4 tiles/box): {$result['boxes_needed']}\n\n";

        echo "Cost Breakdown:\n";
        echo "  Material Cost: £{$result['material_cost']}\n";
        echo "  Labour Cost: £{$result['labour_cost']}\n";
        echo "  Delivery Cost: £{$result['delivery_cost']}\n";
        echo "  Consumables: £{$result['consumables_cost']}\n";
        echo "  Subtotal: £{$result['subtotal']}\n";
        echo "  VAT ({$result['tax_rate_percent']}%): £{$result['tax_amount']}\n";
        echo "  Grand Total: £{$result['grand_total']}\n";
    }

    /**
     * 发送错误响应
     * Send error response
     *
     * @param string $message
     */
    private function sendError($message)
    {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
    }
}

// 使用示例代码
// Usage example

// 如果在命令行中直接运行这个文件
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $controller = new TileController();
    $controller->handleCommandLine($argv);
}