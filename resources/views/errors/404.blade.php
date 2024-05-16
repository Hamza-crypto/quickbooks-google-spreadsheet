<script>
    function generateRandomNumber(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    // Generate a random number between 1 and 100
    const randomNumber = generateRandomNumber(1, 100);

    // Construct the URL with the random number
    const randomWebsite = `https://example${randomNumber}.com`;

    // Redirect to the random website
    window.location.href = randomWebsite;
</script>
