// home.js
document.addEventListener('DOMContentLoaded', function() {
    // Use data passed from PHP
    const currentUser = phpData.currentUser;
    const noteCounts = phpData.noteCounts;
    const notesByCourse = phpData.notesByCourse;
    const coursesDataFromDB = phpData.courses;
    
    const COURSES = coursesDataFromDB.map(course => ({
        id: course.id,
        code: course.code,
        name: course.name,
        level: parseInt(course.level),
        semester: course.semester,
        programs: course.programs.split(',')
    }));

    const PROGRAMS = [
        { id: "CSC", name: "Computer Science", icon: "💻" },
        { id: "AID", name: "AI & Data Science", icon: "🧠" },
        { id: "CNC", name: "Networks & Cybersecurity", icon: "🛡️" },
        { id: "BIF", name: "Bioinformatics", icon: "🧬" }
    ];

    // Populate program filters
    const programFilters = document.getElementById('program-filters');
    PROGRAMS.forEach(p => {
        const btn = document.createElement('button');
        btn.className = 'program-btn';
        btn.innerHTML = `<span class="mr-3 text-xl">${p.icon}</span> ${p.name}`;
        btn.dataset.program = p.id;
        if (p.id === currentUser.program) btn.classList.add('active');
        programFilters.appendChild(btn);
    });

    let selectedProgram = currentUser.program;
    let selectedLevel = currentUser.level;
    let selectedSemester = 'Fall';

    function renderCourses() {
        const filtered = COURSES.filter(c => 
            c.programs.includes(selectedProgram) &&
            c.level === selectedLevel &&
            c.semester === selectedSemester
        );

        document.getElementById('courses-title').textContent = `${selectedProgram} • L${selectedLevel}`;
        document.getElementById('courses-count').textContent = `${filtered.length} Courses`;

        const grid = document.getElementById('courses-grid');
        grid.innerHTML = '';

        filtered.forEach(course => {
            const fullCourseCode = `${course.code} - ${course.name}`;
            const count = noteCounts[fullCourseCode] || 0;

            const card = document.createElement('div');
            card.className = 'course-card';
            card.innerHTML = `
                <div class="course-code">${course.code}</div>
                <h3 class="course-name">${course.name}</h3>
                <div class="open-folder">Open Folder →</div>
                <div class="note-count">${count} note${count !== 1 ? 's' : ''}</div>
            `;
            card.onclick = () => openCourseFolder(course.id, course.code, course.name, count);
            grid.appendChild(card);
        });
    }

    function openCourseFolder(courseId, courseCode, courseName, noteCount) {
        const container = document.getElementById('course-notes-container');
        container.innerHTML = '';

        const fullCourseCode = `${courseCode} - ${courseName}`;
        const notes = notesByCourse[fullCourseCode] || [];

        let html = `<div class="course-folder">
            <button class="upload-note-btn" onclick="quickUpload(${courseId}, '${courseCode}', '${courseName}')" title="Upload note to this course">+</button>
            <div class="course-folder-header">
                <h2 class="course-folder-title">${fullCourseCode}</h2>
                <span class="course-folder-count">${notes.length} note${notes.length !== 1 ? 's' : ''}</span>
            </div>
            <div class="notes-in-folder">`;

        if (notes.length === 0) {
            html += `<div class="no-notes-in-folder">
                <p>No notes uploaded yet for this course.</p>
                <p><small>Be the first to share notes for ${courseCode}!</small></p>
            </div>`;
        } else {
            notes.forEach(note => {
                const extension = note.fileName.split('.').pop().toLowerCase();
                html += `<div class="note-in-folder">
                    <div class="note-header">
                        <div class="note-title-rating">
                            <h4>${note.title}</h4>
                            <div class="note-meta">
                                <strong>By:</strong> ${note.uploaderName} • 
                                <strong>Date:</strong> ${note.uploadDate}
                            </div>
                        </div>
                    </div>
                    ${note.description ? `<p class="note-description">${note.description}</p>` : ''}
                    <a href="${note.filePath}" download class="download-btn">
                        📥 Download ${note.fileName}
                    </a>
                    ${createRatingHTML(note)}`;

                if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                    html += `<img src="${note.filePath}" class="preview-img" alt="Preview">`;
                } else if (extension === 'pdf') {
                    html += `<iframe src="${note.filePath}#view=fitH" class="preview-pdf"></iframe>`;
                }

                html += `</div>`;
            });
        }

        html += `</div></div>`;
        container.innerHTML = html;
        container.scrollIntoView({ behavior: 'smooth' });
    }

    // Create rating HTML
    function createRatingHTML(note) {
        const userRating = note.user_rating || 0;
        const avgRating = parseFloat(note.avg_rating).toFixed(1);
        const totalVotes = parseInt(note.total_votes);
        
        return `
            <div class="rating-container" id="rating-container-${note.id}">
                <div class="rating-title">Rate this note:</div>
                <div class="rating-display">
                    <div class="rating-stars">
                        ${[1, 2, 3, 4, 5].map(i => `
                            <span class="rating-star ${i <= userRating ? 'active' : ''}" 
                                  data-rating="${i}"
                                  onclick="rateNote(${note.id}, ${i})"
                                  title="Rate ${i} star${i > 1 ? 's' : ''}">
                                ★
                            </span>
                        `).join('')}
                    </div>
                    <div class="avg-rating">${avgRating}</div>
                    <div class="total-votes">(${totalVotes} ${totalVotes === 1 ? 'vote' : 'votes'})</div>
                </div>
                ${userRating > 0 ? `<div class="your-rating">You rated: ${userRating}/5</div>` : ''}
                <div class="rating-message" id="rating-message-${note.id}"></div>
                <div class="rating-loading">Saving rating...</div>
            </div>
        `;
    }

    // Quick upload function
    window.quickUpload = function(courseId, courseCode, courseName) {
        sessionStorage.setItem('quickUploadCourseId', courseId);
        sessionStorage.setItem('quickUploadCourseCode', courseCode);
        sessionStorage.setItem('quickUploadCourseName', courseName);
        window.location.href = 'upload.php?quick=true';
    }

    // Rating function
    window.rateNote = async function(noteId, rating) {
        const container = document.getElementById(`rating-container-${noteId}`);
        const message = document.getElementById(`rating-message-${noteId}`);
        const loading = container.querySelector('.rating-loading');
        const stars = container.querySelectorAll('.rating-star');
        
        // Show loading
        if (loading) loading.style.display = 'block';
        
        try {
            const response = await fetch('home.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=rate_note&note_id=${noteId}&rating=${rating}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update UI
                const avgRatingEl = container.querySelector('.avg-rating');
                const totalVotesEl = container.querySelector('.total-votes');
                const userRatingEl = container.querySelector('.your-rating');
                
                // Update stars
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('active');
                        star.classList.add('just-rated');
                        setTimeout(() => star.classList.remove('just-rated'), 500);
                    } else {
                        star.classList.remove('active');
                    }
                });
                
                // Update rating numbers
                if (avgRatingEl) avgRatingEl.textContent = result.avg_rating;
                if (totalVotesEl) totalVotesEl.textContent = `(${result.total_votes} ${result.total_votes === 1 ? 'vote' : 'votes'})`;
                
                if (userRatingEl) {
                    userRatingEl.textContent = `You rated: ${rating}/5`;
                    userRatingEl.style.display = 'block';
                }
                
                // Show success message
                if (message) {
                    message.textContent = 'Rating saved successfully!';
                    message.style.display = 'block';
                    message.style.color = '#10b981';
                    
                    setTimeout(() => {
                        message.style.display = 'none';
                    }, 3000);
                }
                
            } else {
                throw new Error('Rating failed');
            }
        } catch (error) {
            if (message) {
                message.textContent = 'Failed to save rating. Please try again.';
                message.style.display = 'block';
                message.style.color = '#ef4444';
                
                setTimeout(() => {
                    message.style.display = 'none';
                }, 3000);
            }
        } finally {
            if (loading) loading.style.display = 'none';
        }
    }

    // Event listeners
    document.getElementById('program-filters').addEventListener('click', e => {
        const btn = e.target.closest('.program-btn');
        if (btn) {
            document.querySelectorAll('.program-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedProgram = btn.dataset.program;
            renderCourses();
        }
    });

    document.querySelectorAll('.level-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.level-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedLevel = Number(btn.dataset.level);
            renderCourses();
        });
    });

    document.querySelectorAll('.semester-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.semester-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedSemester = btn.dataset.semester;
            renderCourses();
        });
    });

    renderCourses();
});  