                Click the button and allow location permission to detect your current GPS position.
            </small>

            <a id="mapLink" href="#" target="_blank" rel="noopener noreferrer">
                View detected location on Google Maps
            </a>
        </div>

        <div class="form-group">
            <label for="image">Upload Image (Optional)</label>

            <input
                type="file"
                name="image"
                id="image"
                accept=".jpg,.jpeg,.png,.webp"
            >

            <small>Allowed: JPG, PNG, WEBP. Maximum size: 2 MB.</small>

            <img id="imagePreview" alt="Image preview">
        </div>

        <button type="submit" name="submit_report" class="submit-btn">
            Submit Complaint
        </button>

    </form>

</div>

<script>
// Image preview
document.getElementById("image").addEventListener("change", function () {
    const preview = document.getElementById("imagePreview");
    const file = this.files[0];

    if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = "block";
    } else {
        preview.style.display = "none";
        preview.removeAttribute("src");
    }
});

// Get the user's current GPS location when they request it.
const getLocationBtn = document.getElementById("getLocationBtn");
const locationInput = document.getElementById("location");
const locationStatus = document.getElementById("locationStatus");
const mapLink = document.getElementById("mapLink");

getLocationBtn.addEventListener("click", function () {
    if (!("geolocation" in navigator)) {
        locationStatus.textContent = "GPS location is not supported by this browser. Please enter your location manually.";
        return;
    }

    getLocationBtn.disabled = true;
    getLocationBtn.textContent = "Detecting location...";
    locationStatus.textContent = "Requesting GPS permission. Please allow location access.";

    navigator.geolocation.getCurrentPosition(
        function (position) {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            const accuracy = Math.round(position.coords.accuracy);

            // Store coordinates in the existing location database field.
            locationInput.value =
                "Latitude: " + latitude.toFixed(6) +
                ", Longitude: " + longitude.toFixed(6);

            // Create a map link for the detected coordinates.
            const mapsUrl = "https://www.google.com/maps?q=" +
                encodeURIComponent(latitude + "," + longitude);

            mapLink.href = mapsUrl;
            mapLink.style.display = "inline-block";

            locationStatus.textContent =
                "Location detected successfully. GPS accuracy: approximately " +
                accuracy + " metres.";

            getLocationBtn.textContent = "📍 Update My Location";
            getLocationBtn.disabled = false;
        },
        function (error) {
            let errorMessage;

            switch (error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = "Location permission was denied. Allow location access in your browser settings or enter the location manually.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = "Your current location is unavailable. Check your device's location settings and try again.";
                    break;
                case error.TIMEOUT:
                    errorMessage = "Location request timed out. Please try again.";
                    break;
                default:
                    errorMessage = "Unable to detect your location. Please enter it manually.";
            }

            locationStatus.textContent = errorMessage;
            getLocationBtn.disabled = false;
            getLocationBtn.textContent = "📍 Try Again";
        },
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }
    );
});
</script>

</body>
</html>
