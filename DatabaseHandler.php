<?php
class DatabaseHandler
{
    private $conn;

    public function __construct($servername, $username, $password, $database)
    {
        $this->conn = new mysqli($servername, $username, $password, $database);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function insertData($dataList)
    {   
        // $jsonFilePath = 'data.json';

        // // Read the JSON file content
        // $jsonFileContent = file_get_contents($jsonFilePath);

        // // Parse the JSON data
        // $dataList = json_decode($jsonFileContent, true);
        foreach ($dataList as $record) {
            $color = mysqli_real_escape_string($this->conn,$record['color']);
            $get_data = "SELECT * FROM items where upc = '{$record['upc']}'";
            $result = $this->conn->query($get_data);
            if ($result) {
                $row = $result->fetch_assoc();
                if ($row) {
                    $update_data = "UPDATE items
                        SET style_number='{$record['styleName']}',
                            availability='{$record['availability']}',
                            availability_date = '{$record['available_date']}',
                            box_size='{$record['size']}',
                            box_a='{$record['a']}',
                            box_b='{$record['b']}',
                            box_ed='{$record['ed']}',
                            box_dbl='{$record['dbl']}',
                            lux_color_code = '$color'
                        WHERE upc = '{$record['upc']}'";
                    if ($this->conn->query($update_data) === TRUE) {
                        echo "Record updated successfully<br>";
                    } else {
                        echo "Error update sql query: " . $update_data . "<br>" . $this->conn->error;
                    }
                } else {
                    $sql = "INSERT INTO  items VALUES (
                        '','','','','','','{$record['upc']}','{$record['styleName']}',
                        '{$record['availability']}','','{$record['available_date']}','','{$record['size']}',
                        '{$record['a']}','{$record['b']}','{$record['ed']}','{$record['dbl']}','','','','','','',
                        '','','','','',
                        '','','$color','','',
                        '','','',''
                        '','','',
                        '','','','','',
                        '','','',
                        '','','',
                        '','','',
                        '','','','','','',
                        '','','','','','',
                        '','','','','','',
                        '','',''
                    )";
                    if ($this->conn->query($sql) === TRUE) {
                        echo "Record added successfully<br>";
                    } else {
                        echo "Error insert sql query: " . $sql . "<br>" . $this->conn->error;
                    }
                }
            } else {
                echo "Error getting data: " . $this->conn->error;
            }
        }
    }

    public function closeConnection()
    {
        $this->conn->close();
    }
}

?>
