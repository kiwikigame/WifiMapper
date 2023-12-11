<?php

include 'db_config.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Carte WiFi</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
</head>
<body>

<div id="map" style="height: 100vh;"></div>

<div id="filter-container" style="position: absolute; top: 10px; right: 10px; z-index: 1000;">
    <button onclick="toggleFilters()">Filtres</button>
    <div id="filter-dropdown" style="display: none; background-color: #f1f1f1; padding: 10px;">
        <h2>Filtrer par Sécurité</h2>
        <form id="filterForm">
            <?php
            while ($rowSecurite = mysqli_fetch_assoc($resultatSecurite)) {
                echo '<input type="checkbox" name="securite[]" value="' . escapeString($rowSecurite['Securite']) . '">' . $rowSecurite['Securite'] . '<br>';
            }
            ?>
            <br>
            <button type="button" onclick="filterMarkers()">Filtrer</button>
        </form>

        <h2>Rechercher une Ville</h2>
        <input type="text" id="cityInput" placeholder="Entrez le nom de la ville">
        <button type="button" onclick="searchCity()">Rechercher</button>
    </div>
</div>

<script>
    var map = L.map('map').setView([0, 0], 2); 
    var markers = L.markerClusterGroup();

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    <?php
    $resultat = mysqli_query($connexion, "SELECT * FROM wifitrg");
    while ($row = mysqli_fetch_assoc($resultat)) {
        echo "var nbbcValue = " . $row['NBBC'] . ";\n";
        echo "var circleColor = getCircleColor(nbbcValue);\n";
        echo "var circle = L.circle([" . $row['LAT'] . ", " . $row['LON'] . "], {
                color: circleColor,
                fillColor: circleColor,
                fillOpacity: 0.5,
                radius: 2
            })
            .bindPopup('<b>SSID:</b> " . escapeString($row['SSID']) . "<br>"
            . "<b>Networkmac:</b> " . escapeString($row['Networkmac']) . "<br>"
            . "<b>SIGMOY:</b> " . escapeString($row['SIGMOY']) . "<br>"
            . "<b>Premiereaparition:</b> " . escapeString($row['Premiereaparition']) . "<br>"
            . "<b>derniereaparition:</b> " . escapeString($row['derniereaparition']) . "<br>"
            . "<b>Securite:</b> " . escapeString($row['Securite']) . "<br>"
            . "<b>NBBC:</b> " . escapeString($row['NBBC']) . "<br>"
            . "<b>DernierScaner:</b> " . escapeString($row['DernierScaner']) . "<br>"
            . "<b>PremierScaner:</b> " . escapeString($row['PremierScaner']) . "')
            .addTo(markers);\n";
    }
    ?>

    map.addLayer(markers);

    function getCircleColor(nbbcValue) {
        var startColor = [255, 0, 0]; // Rouge
		var midColor = [0, 255, 0];   // vert
        var endColor = [0, 0, 255];   // Bleu

		if(nbbcValue <= 10){
			var ratio = nbbcValue / 10;
			var color = [
				Math.round(startColor[0] + ratio * (midColor[0] - startColor[0])),
				Math.round(startColor[1] + ratio * (midColor[1] - startColor[1])),
				Math.round(startColor[2] + ratio * (midColor[2] - startColor[2]))
			];
		}else{
			var ratio2 = (nbbcValue-10) / 10;
			var color = [
				Math.round(midColor[0] + ratio2 * (endColor[0] - midColor[0])),
				Math.round(midColor[1] + ratio2 * (endColor[1] - midColor[1])),
				Math.round(midColor[2] + ratio2 * (endColor[2] - midColor[2]))
			];
		}
        

        return 'rgb(' + color.join(',') + ')';
    }

    function filterMarkers() {
        var selectedSecurite = [];

        var checkboxes = document.getElementsByName('securite[]');
        checkboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                selectedSecurite.push(checkbox.value);
            }
        });

        markers.clearLayers();

        <?php
        $resultat = mysqli_query($connexion, "SELECT * FROM wifitrg");
        while ($row = mysqli_fetch_assoc($resultat)) {
            echo "var nbbcValue = " . $row['NBBC'] . ";\n";
            echo "var circleColor = getCircleColor(nbbcValue);\n";
            echo "if (['" . implode("','", array_map('escapeString', explode(',', $row['Securite']))) . "'].some(value => selectedSecurite.includes(value))) {
                    var circle = L.circle([" . $row['LAT'] . ", " . $row['LON'] . "], {
                        color: circleColor,
                        fillColor: circleColor,
                        fillOpacity: 0.5,
                        radius: 2
                    })
                    .bindPopup('<b>SSID:</b> " . escapeString($row['SSID']) . "<br>"
                    . "<b>Networkmac:</b> " . escapeString($row['Networkmac']) . "<br>"
                    // ... (other popup content)
                    . "<b>PremierScaner:</b> " . escapeString($row['PremierScaner']) . "')
                    .addTo(markers);
                }\n";
        }
        ?>
    }

    function searchCity() {
        var cityName = document.getElementById('cityInput').value;

        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + cityName)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    var city = data[0];
                    var lat = parseFloat(city.lat);
                    var lon = parseFloat(city.lon);

                    map.setView([lat, lon], 12);
                } else {
                    alert('Ville non trouvée. Veuillez réessayer.');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la recherche de la ville:', error);
                alert('Une erreur s\'est produite lors de la recherche de la ville. Veuillez réessayer.');
            });
    }

    function toggleFilters() {
        var filterDropdown = document.getElementById("filter-dropdown");
        if (filterDropdown.style.display === "none") {
            filterDropdown.style.display = "block";
        } else {
            filterDropdown.style.display = "none";
        }
    }
</script>

</body>
</html>

<?php
mysqli_close($connexion);
?>
