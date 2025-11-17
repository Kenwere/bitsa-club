// Enhanced Dashboard JavaScript
let isLoadingPosts = false;
let currentLikes = {};

// Mobile sidebar functionality
const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const userSidebar = document.getElementById('userSidebar');

if (mobileSidebarToggle) {
    mobileSidebarToggle.addEventListener('click', function() {
        userSidebar.classList.toggle('mobile-open');
        sidebarOverlay.classList.toggle('active');
    });
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', function() {
        userSidebar.classList.remove('mobile-open');
        sidebarOverlay.classList.remove('active');
    });
}

// Desktop sidebar toggle
const userMain = document.getElementById('userMain');
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');

if (window.innerWidth > 768 && sidebarToggle) {
    sidebarToggle.addEventListener('click', function() {
        userSidebar.classList.toggle('collapsed');
        userMain.classList.toggle('expanded');
        
        if (userSidebar.classList.contains('collapsed')) {
            sidebarToggleIcon.className = 'bi bi-chevron-right';
        } else {
            sidebarToggleIcon.className = 'bi bi-chevron-left';
        }
    });
} else if (sidebarToggle) {
    sidebarToggle.style.display = 'none';
}

// Navigation
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
        if (this.tagName === 'BUTTON' && this.type === 'submit') return;
        
        e.preventDefault();
        const section = this.getAttribute('data-section');
        
        document.querySelectorAll('.nav-item').forEach(nav => {
            nav.classList.remove('active');
        });
        this.classList.add('active');
        
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });
        document.getElementById(section + '-section').classList.add('active');
        
        // Close mobile sidebar
        if (window.innerWidth <= 768) {
            userSidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
        }
    });
});

// Theme Toggle
const themeToggle = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');
const themeText = document.getElementById('themeText');
const html = document.documentElement;

const currentTheme = localStorage.getItem('theme') || 'dark';
html.setAttribute('data-theme', currentTheme);
updateThemeIcon(currentTheme);

if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const theme = html.getAttribute('data-theme');
        const newTheme = theme === 'light' ? 'dark' : 'light';
        
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme);
    });
}

function updateThemeIcon(theme) {
    if (theme === 'dark') {
        themeIcon.className = 'bi bi-sun';
        themeText.textContent = 'Light Mode';
    } else {
        themeIcon.className = 'bi bi-moon';
        themeText.textContent = 'Dark Mode';
    }
}

// Post functionality
const createPostForm = document.getElementById('createPostForm');
if (createPostForm) {
    createPostForm.addEventListener('submit', function(e) {
        e.preventDefault();
        createPost();
    });
}

// Show image file name when selected
const postImage = document.getElementById('postImage');
if (postImage) {
    postImage.addEventListener('change', function(e) {
        const fileName = this.files[0]?.name || '';
        const fileNameDisplay = document.getElementById('imageFileName');
        if (fileNameDisplay) {
            fileNameDisplay.textContent = fileName;
        }
    });
}

async function createPost() {
    const content = document.getElementById('postContent').value.trim();
    const imageFile = document.getElementById('postImage').files[0];
    const postButton = document.getElementById('postButton');
    
    if (!content && !imageFile) {
        showTimedAlert('Please add some content or an image to your post.', 'danger');
        return;
    }

    postButton.disabled = true;
    postButton.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Posting...';

    const formData = new FormData();
    formData.append('content', content);
    if (imageFile) {
        formData.append('image', imageFile);
    }
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

    try {
        const response = await fetch('../api/posts.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('postContent').value = '';
            document.getElementById('postImage').value = '';
            const fileNameDisplay = document.getElementById('imageFileName');
            if (fileNameDisplay) {
                fileNameDisplay.textContent = '';
            }
            showTimedAlert('Post created successfully!', 'success');
            loadPosts();
        } else {
            showTimedAlert(data.message || 'Failed to create post', 'danger');
        }
    } catch (error) {
        console.error('Error creating post:', error);
        showTimedAlert('Network error. Please check your connection and try again.', 'danger');
    } finally {
        postButton.disabled = false;
        postButton.innerHTML = '<i class="bi bi-send"></i> Post';
    }
}

async function loadPosts() {
    if (isLoadingPosts) return;
    
    isLoadingPosts = true;
    const feed = document.getElementById('postsFeed');
    if (!feed) return;
    
    feed.innerHTML = '<div class="loading"><i class="bi bi-arrow-repeat spinner"></i> Loading posts...</div>';

    try {
        const response = await fetch('../api/posts.php');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const posts = await response.json();

        if (posts.length === 0) {
            feed.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h4>No Posts Yet</h4>
                    <p>Be the first to share something with the community!</p>
                </div>
            `;
            return;
        }

        feed.innerHTML = posts.map(post => `
            <div class="post-card" id="post-${post.id}">
                <div class="post-header">
                    <div class="post-avatar">
                        ${post.user_name ? post.user_name.charAt(0).toUpperCase() : 'U'}
                    </div>
                    <div class="post-user-info">
                        <div class="post-user">${post.user_name || 'User'} <span class="post-username">@${post.username || 'user'}</span></div>
                        <div class="post-time">${formatTime(post.created_at)}</div>
                    </div>
                </div>
                
                ${post.content ? `<div class="post-content">${escapeHtml(post.content)}</div>` : ''}
                
                ${post.image ? `
                    <div class="post-image-container">
                        <img src="../assets/uploads/posts/${post.image}" class="post-image" alt="Post image" onerror="this.style.display='none'">
                    </div>
                ` : ''}
                
                <div class="post-actions">
                    <button class="post-action ${currentLikes[post.id] ? 'liked' : ''}" onclick="likePost(${post.id})" id="like-btn-${post.id}">
                        <i class="bi ${currentLikes[post.id] ? 'bi-heart-fill' : 'bi-heart'}"></i> 
                        <span id="like-count-${post.id}">${post.likes_count || 0}</span>
                    </button>
                    <button class="post-action" onclick="toggleComments(${post.id})" id="comment-btn-${post.id}">
                        <i class="bi bi-chat"></i> 
                        <span id="comment-count-${post.id}">${post.comments_count || 0}</span>
                    </button>
                </div>
                
                <div class="comments-section" id="comments-${post.id}" style="display: none;">
                    <div class="comment-form">
                        <input type="text" class="comment-input" placeholder="Write a comment..." id="comment-input-${post.id}">
                        <button class="btn btn-sm" style="background: var(--accent-cyan); color: #000;" onclick="addComment(${post.id})">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                    <div class="comments-list" id="comments-list-${post.id}">
                        <!-- Comments will be loaded here -->
                    </div>
                </div>
            </div>
        `).join('');

    } catch (error) {
        console.error('Error loading posts:', error);
        feed.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                Failed to load posts. Please try again later.
            </div>
        `;
    } finally {
        isLoadingPosts = false;
    }
}

// Like post function
async function likePost(postId) {
    try {
        const response = await fetch(`../api/posts.php?action=like&post_id=${postId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="csrf_token"]').value
            }
        });

        const data = await response.json();

        if (data.success) {
            // Update the like count and state in the UI
            const likeButton = document.getElementById(`like-btn-${postId}`);
            const likeCount = document.getElementById(`like-count-${postId}`);
            
            currentLikes[postId] = data.liked;
            
            if (likeButton && likeCount) {
                likeButton.classList.toggle('liked', data.liked);
                likeButton.innerHTML = `
                    <i class="bi ${data.liked ? 'bi-heart-fill' : 'bi-heart'}"></i> 
                    <span id="like-count-${postId}">${data.likes_count}</span>
                `;
            }
            
            showTimedAlert(data.liked ? 'Post liked!' : 'Post unliked!', 'success');
        } else {
            showTimedAlert(data.message || 'Failed to like post. Please try again.', 'danger');
        }
    } catch (error) {
        console.error('Error liking post:', error);
        showTimedAlert('Network error. Please try again.', 'danger');
    }
}

function toggleComments(postId) {
    const commentsSection = document.getElementById(`comments-${postId}`);
    const commentBtn = document.getElementById(`comment-btn-${postId}`);
    
    if (!commentsSection || !commentBtn) return;
    
    if (commentsSection.style.display === 'none') {
        commentsSection.style.display = 'block';
        commentBtn.classList.add('active');
        loadComments(postId);
    } else {
        commentsSection.style.display = 'none';
        commentBtn.classList.remove('active');
    }
}

async function loadComments(postId) {
    const commentsList = document.getElementById(`comments-list-${postId}`);
    if (!commentsList) return;
    
    commentsList.innerHTML = '<div class="loading"><i class="bi bi-arrow-repeat spinner"></i> Loading comments...</div>';

    try {
        const response = await fetch(`../api/posts.php?action=comments&post_id=${postId}`);
        if (!response.ok) throw new Error('Failed to load comments');
        
        const comments = await response.json();
        
        if (comments.length === 0) {
            commentsList.innerHTML = '<div class="text-muted text-center py-3">No comments yet. Be the first to comment!</div>';
            return;
        }

        commentsList.innerHTML = comments.map(comment => `
            <div class="comment">
                <div class="comment-avatar">
                    ${comment.user_name ? comment.user_name.charAt(0).toUpperCase() : 'U'}
                </div>
                <div class="comment-content">
                    <div class="comment-user">${comment.user_name || 'User'} <span class="comment-username">@${comment.username || 'user'}</span></div>
                    <div class="comment-text">${escapeHtml(comment.content)}</div>
                    <div class="comment-time">${formatTime(comment.created_at)}</div>
                </div>
            </div>
        `).join('');

    } catch (error) {
        console.error('Error loading comments:', error);
        commentsList.innerHTML = '<div class="alert alert-danger">Failed to load comments</div>';
    }
}

async function addComment(postId) {
    const commentInput = document.getElementById(`comment-input-${postId}`);
    if (!commentInput) return;
    
    const content = commentInput.value.trim();
    
    if (!content) {
        showTimedAlert('Please enter a comment', 'warning');
        return;
    }

    try {
        const response = await fetch(`../api/posts.php?action=comment&post_id=${postId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="csrf_token"]').value
            },
            body: JSON.stringify({ content: content })
        });

        const data = await response.json();

        if (data.success) {
            commentInput.value = '';
            showTimedAlert('Comment added successfully!', 'success');
            loadComments(postId);
            
            // Update comment count
            const commentCount = document.getElementById(`comment-count-${postId}`);
            if (commentCount) {
                const currentCount = parseInt(commentCount.textContent) || 0;
                commentCount.textContent = currentCount + 1;
            }
        } else {
            showTimedAlert(data.message || 'Failed to add comment. Please try again.', 'danger');
        }
    } catch (error) {
        console.error('Error adding comment:', error);
        showTimedAlert('Network error. Please try again.', 'danger');
    }
}

// Meeting functionality
async function createMeeting() {
    const form = document.getElementById('createMeetingForm');
    const createBtn = document.getElementById('createMeetingBtn');
    
    if (!form || !createBtn) return;

    const formData = new FormData(form);

    // Validate form
    const title = formData.get('title');
    const date = formData.get('date');
    const time = formData.get('time');

    if (!title || !date || !time) {
        showTimedAlert('Please fill in all required fields', 'danger');
        return;
    }

    // Disable button and show loading
    createBtn.disabled = true;
    createBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Creating...';

    try {
        const response = await fetch('../api/meetings.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showTimedAlert('Meeting created successfully!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createMeetingModal'));
            if (modal) {
                modal.hide();
            }
            
            form.reset();
            location.reload(); // Reload to show new meeting
        } else {
            showTimedAlert(data.message || 'Failed to create meeting', 'danger');
        }
    } catch (error) {
        console.error('Error creating meeting:', error);
        showTimedAlert('Failed to create meeting. Please try again.', 'danger');
    } finally {
        // Re-enable button
        createBtn.disabled = false;
        createBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Create Meeting';
    }
}

// Delete meeting function
async function deleteMeeting(meetingId) {
    if (!confirm('Are you sure you want to delete this meeting? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch(`../api/meetings.php?action=delete&meeting_id=${meetingId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="csrf_token"]').value
            }
        });

        const data = await response.json();

        if (data.success) {
            showTimedAlert('Meeting deleted successfully!', 'success');
            location.reload();
        } else {
            showTimedAlert(data.message || 'Failed to delete meeting', 'danger');
        }
    } catch (error) {
        console.error('Error deleting meeting:', error);
        showTimedAlert('Failed to delete meeting: ' + error.message, 'danger');
    }
}

async function joinMeeting(meetingId) {
    try {
        const response = await fetch(`../api/meetings.php?action=join&meeting_id=${meetingId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="csrf_token"]').value
            }
        });

        const data = await response.json();

        if (data.success) {
            showTimedAlert('Joining meeting...', 'success');
            // Redirect to meeting room
            window.location.href = `meeting-room.php?meeting_id=${meetingId}`;
        } else {
            showTimedAlert(data.message || 'Failed to join meeting', 'danger');
        }
    } catch (error) {
        console.error('Error joining meeting:', error);
        showTimedAlert('Failed to join meeting: ' + error.message, 'danger');
    }
}

// Profile functionality
const profileForm = document.getElementById('profileForm');
if (profileForm) {
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        updateProfile();
    });
}

async function updateProfile() {
    const form = document.getElementById('profileForm');
    const updateBtn = document.getElementById('updateProfileBtn');
    
    if (!form || !updateBtn) return;

    const formData = new FormData(form);

    // Disable button and show loading
    updateBtn.disabled = true;
    updateBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Updating...';

    try {
        const response = await fetch('../api/profile.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showTimedAlert('Profile updated successfully!', 'success');
            // Update the displayed profile information
            const profileName = document.getElementById('profileName');
            const profileUsername = document.getElementById('profileUsername');
            if (profileName) profileName.textContent = formData.get('name');
            if (profileUsername) profileUsername.textContent = '@' + formData.get('username');
        } else {
            showTimedAlert(data.message || 'Failed to update profile', 'danger');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showTimedAlert('Failed to update profile: ' + error.message, 'danger');
    } finally {
        // Re-enable button
        updateBtn.disabled = false;
        updateBtn.innerHTML = '<i class="bi bi-check-circle"></i> Update Profile';
    }
}

// Event functionality
async function attendEvent(eventId) {
    try {
        const response = await fetch(`../api/events.php?action=attend&event_id=${eventId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="csrf_token"]').value
            }
        });

        const data = await response.json();

        if (data.success) {
            showTimedAlert('You are now attending this event!', 'success');
        } else {
            showTimedAlert(data.message || 'Failed to attend event', 'danger');
        }
    } catch (error) {
        console.error('Error attending event:', error);
        showTimedAlert('Network error. Please try again.', 'danger');
    }
}

// Utility functions
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatTime(dateString) {
    if (!dateString) return 'Unknown time';
    
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    
    return date.toLocaleDateString();
}

// Enhanced timed alert function
function showTimedAlert(message, type = 'info') {
    // Remove any existing alerts first
    document.querySelectorAll('.timed-alert').forEach(alert => alert.remove());

    const alert = document.createElement('div');
    alert.className = `timed-alert alert alert-${type}`;
    alert.innerHTML = `
        <i class="bi ${getAlertIcon(type)}"></i>
        ${message}
    `;
    
    // Fixed styling without blur/opacity issues
    alert.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideInRight 0.3s ease, slideOutRight 0.3s ease 4.7s forwards;
        background: ${type === 'success' ? 'var(--accent-success)' : 
                     type === 'danger' ? 'var(--accent-danger)' : 
                     type === 'warning' ? 'var(--accent-warning)' : 'var(--accent-primary)'} !important;
        color: white !important;
        border: none !important;
        backdrop-filter: none !important;
        opacity: 1 !important;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        font-weight: 500;
    `;
    
    document.body.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

function getAlertIcon(type) {
    const icons = {
        'success': 'bi-check-circle-fill',
        'danger': 'bi-exclamation-triangle-fill',
        'warning': 'bi-exclamation-triangle-fill',
        'info': 'bi-info-circle-fill'
    };
    return icons[type] || 'bi-info-circle-fill';
}

// Handle window resize
window.addEventListener('resize', function() {
    if (window.innerWidth <= 768) {
        if (sidebarToggle) sidebarToggle.style.display = 'none';
        if (userSidebar) {
            userSidebar.classList.remove('collapsed');
            userSidebar.classList.remove('mobile-open');
        }
        if (userMain) userMain.classList.remove('expanded');
        if (sidebarOverlay) sidebarOverlay.classList.remove('active');
    } else {
        if (sidebarToggle) sidebarToggle.style.display = 'flex';
        if (userSidebar) userSidebar.classList.remove('mobile-open');
        if (sidebarOverlay) sidebarOverlay.classList.remove('active');
    }
});

// Auto-set meeting date to today
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.querySelector('input[name="date"]');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
        dateInput.value = today;
    }

    // Set default time to next hour
    const timeInput = document.querySelector('input[name="time"]');
    if (timeInput) {
        const nextHour = new Date();
        nextHour.setHours(nextHour.getHours() + 1);
        nextHour.setMinutes(0);
        timeInput.value = nextHour.toTimeString().substring(0, 5);
    }

    // Load initial data
    loadPosts();
});

// Auto-refresh posts every 30 seconds
setInterval(loadPosts, 30000);