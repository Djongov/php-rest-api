<?php declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'GET') :

    if (file_exists(dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . '.env')) {
        die('No work to be done here');
    }
?>
<h2>This is a form to help you create a .env file not by hand</h2>
<p>Have in mind that most of the settings that control this app are in /config folder.</p>
<form id="env">
    <label for="DB_SSL">DB_SSL:</label>
    <select id="DB_SSL" name="DB_SSL">
        <option value="false">false</option>
        <option value="true">true</option>
    </select><br><br>
    <label for="DB_HOST">DB_HOST:</label>
    <input type="text" id="DB_HOST" name="DB_HOST" required placeholder="localhost" value="localhost"><br><br>

    <label for="DB_USER">DB_USER:</label>
    <input type="text" id="DB_USER" name="DB_USER" placeholder="root" value="root" required><br><br>

    <label for="DB_PASS">DB_PASS:</label>
    <input type="password" id="DB_PASS" name="DB_PASS" required><br><br>

    <label for="DB_NAME">DB_NAME:</label>
    <input type="text" id="DB_NAME" name="DB_NAME" placeholder="dashboard" value="dashboard" required><br><br>

    <label for="DB_PORT">DB_PORT:</label>
    <input type="number" id="DB_PORT" name="DB_PORT" value="3306" required><br><br>

    <label for="DB_DRIVER">DB_DRIVER:</label>
    <select id="DB_DRIVER" name="DB_DRIVER">
        <option value="mysql">MySQL</option>
        <option value="pgsql">PostgreSQL</option>
        <option value="sqlsrv">SQL Server</option>
        <option value="sqlite">SQLite</option>
    </select><br><br>

    <label for="SENDGRID">SENDGRID
        <input type="checkbox" id="SENDGRID" name="SENDGRID">
    </label>
    

    <button type="submit">Submit</button>
</form>

<script nonce="1nL1n3JsRuN1192kwoko2k323WKE">
const form = document.getElementById('env');

form.action = 'POST';

form.addEventListener('submit', (event) => {
    event.preventDefault(); // Prevent the default form submission
    event.submitter.disabled = true;
    event.submitter.innerHTML = 'loading...';
    const formData = new FormData(form); // Serialize form data
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        event.submitter.innerHTML = 'Submit';
        return response.text(); // Parse response as text
    })
    .then(data => {
        event.submitter.disabled = false;
        // Remove any existing div with id "responseDiv"
        const existingResponseDiv = document.getElementById('responseDiv');
        if (existingResponseDiv) {
            existingResponseDiv.remove();
        }
        // Create a new div element
        const responseDiv = document.createElement('div');
        // Set the id of the div
        responseDiv.id = 'responseDiv';
        // Set the inner HTML of the div to the response text
        responseDiv.innerHTML = data;
        // Append the div to the document body
        document.body.appendChild(responseDiv);

        if (data === 'The .env file has been created successfully.') {
            responseDiv.innerText += ` Redirecting to root...`;
            responseDiv.style.color = 'green';
            setInterval(() => {
                window.location = '/';
            }, 2000)
        }
    })
    .catch(error => {
        // Handle errors
        console.error('There was a problem with your fetch operation:', error);
    });
});
</script>

<?php endif;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    if (file_exists(dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . '.env')) {
        die('No work to be done here');
    }

    // Create the .env file
    $envContentArray = $_POST;

    if (isset($_POST['SENDGRID']) && $_POST["SENDGRID"] === 'on') {
        $envContentArray['SENDGRID_ENABLED'] = 'true';
    } else {
        $envContentArray['SENDGRID_ENABLED'] = 'false';
    }
    
    // Let's unset the ones that we don't want to be in the .env file
    unset($envContentArray['local_login']);
    unset($envContentArray['Google_login']);
    unset($envContentArray['Microsoft_LIVE_login']);
    unset($envContentArray['Entra_ID_login']);
    unset($envContentArray['SENDGRID']);

    $envContent = '';

    foreach ($envContentArray as $key => $value) {
        $updatedValue = '';
        if ($value === 'true' || $value === 'false') {
            $updatedValue = $value;
        } else {
            $updatedValue = '"' . $value . '"';
        }
        $envContent .= $key . '=' . $updatedValue . '' . PHP_EOL;
    }


    $envFilePath = dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . '.env';

    $fileHandle = fopen($envFilePath, 'w');

    // Write the content to the file
    if ($fileHandle) {
        fwrite($fileHandle, $envContent);
        fclose($fileHandle);
        echo "The .env file has been created successfully.";
    } else {
        http_response_code(404);
        echo "Unable to create the .env file.";
    }

}
