document.addEventListener('DOMContentLoaded', () => {
    const menuItems = document.querySelectorAll('.menu-item');
    const sectionTitle = document.getElementById('section-title');
    const sectionContent = document.getElementById('section-content');
    
    // Function to load a PHP file into the main content
    function loadSection(section) {
        // Update the title
        sectionTitle.textContent = sectionToTitle(section);

        // Make AJAX request to load content
        fetch(`${section}.php`) // Make sure the PHP files are in the correct location
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load content.');
                }
                return response.json(); // Expecting JSON response from PHP
            })
            .then(data => {
                if (data.success) {
                    // Optionally, insert content if needed (for example, from 'profile.php')
                    sectionContent.innerHTML = "<p>" + data.message + "</p>"; // Just for example
                    
                    // Redirect to the appropriate page based on the section
                    window.location.href = `${section}.php`; // Redirect to a dynamic page based on the section name
                } else {
                    sectionContent.innerHTML = `<p style="color: red;">Error: ${data.message}</p>`;
                }
            })
            .catch(error => {
                sectionContent.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
            });
    }

    // Helper to map section to title
    function sectionToTitle(section) {
        const titles = {
            profile: 'User Profile',
            search: 'Search & Browse',
            bookDetails: 'Book Details',
            borrowed: 'Borrowed Books',
            reservation: 'Book Reservation',
            history: 'Borrowing History',
            fines: 'Fines',
            notifications: 'Notifications',
            wishlist: 'Wishlist',
            recommendations: 'Recommendations',
            reviews: 'Reviews & Ratings',
            ebooks: 'eBooks / PDFs',
            calendar: 'Events Calendar',
            chat: 'Ask Librarian',
            feedback: 'Feedback',
            settings: 'Settings'
        };
        return titles[section] || 'Library Dashboard';
    }
  
    // Add event listener to each menu item
    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            const section = item.getAttribute('data-section'); // Get the section name from the data attribute
  
            // Remove 'active' class from all and set it for the current item
            menuItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
  
            // Load the section
            loadSection(section);
        });
    });
  
    // Load the default section on initial page load
    loadSection('profile'); // Default section when page loads (can be adjusted if needed)
});
