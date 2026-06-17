# JavaScript Coding Rules

## Overview
These rules apply to all JavaScript files in the SPMB project.

---

## 1. Modern JavaScript (ES6+)

```javascript
// ✅ Use const/let instead of var
const API_URL = '/api/v1';
let isLoading = false;

// ✅ Arrow functions
const getStudent = async (id) => {
    return await fetch(`${API_URL}/students/${id}`);
};

// ✅ Template literals
const message = `Hello ${name}, your ID is ${studentId}`;

// ✅ Destructuring
const { name, email,jurusan } = studentData;

// ✅ Object shorthand
const createStudent = (name, email) => ({ name, email });
```

---

## 2. Async/Await

```javascript
// ✅ Preferred over .then()
async function submitForm(formData) {
    try {
        isLoading = true;
        updateUI();
        
        const response = await fetch('/submit.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('Network error');
        }
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = result.redirect;
        } else {
            showError(result.message);
        }
        
    } catch (error) {
        console.error('Error:', error);
        showError('Terjadi kesalahan. Silakan coba lagi.');
    } finally {
        isLoading = false;
        updateUI();
    }
}

// ❌ Avoid callback hell
fetch(url, callback1)
    .then(callback2)
    .then(callback3);
```

---

## 3. Form Validation

```javascript
// ✅ Client-side validation before submit
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registrationForm');
    
    if (form) {
        form.addEventListener('submit', (e) => {
            const name = document.getElementById('nama').value.trim();
            
            if (!name || name.length < 3) {
                e.preventDefault();
                showError('Nama minimal 3 karakter');
                return;
            }
            
            // Continue with form submission
        });
    }
});
```

---

## 4. Event Handling

```javascript
// ✅ Use addEventListener
const button = document.getElementById('submitBtn');

button.addEventListener('click', async (e) => {
    e.preventDefault();
    // Handle click
});

// ❌ Inline handlers
<button onclick="submitForm()">Submit</button>

// ✅ Event delegation for lists
document.querySelector('.student-list').addEventListener('click', (e) => {
    const deleteBtn = e.target.closest('.btn-delete');
    if (deleteBtn) {
        handleDelete(deleteBtn.dataset.id);
    }
});
```

---

## 5. XSS Prevention

```javascript
// ✅ Always sanitize user content
const sanitizeHTML = (str) => {
    const temp = document.createElement('div');
    temp.textContent = str;
    return temp.innerHTML;
};

// ✅ Display user content safely
element.textContent = userInput;
// or
element.innerHTML = sanitizeHTML(userInput);

// ❌ Dangerous
element.innerHTML = userInput;
```

---

## 6. AJAX/Fetch

```javascript
// ✅ POST request with FormData
const submitForm = async (formElement) => {
    const formData = new FormData(formElement);
    
    // Add CSRF token
    formData.append('csrf_token', formElement.csrf_token.value);
    
    const response = await fetch(formElement.action, {
        method: 'POST',
        body: formData
    });
    
    return await response.json();
};

// ✅ GET request with query params
const searchStudents = async (query) => {
    const params = new URLSearchParams({ q: query });
    const response = await fetch(`/api/students?${params}`);
    return await response.json();
};
```

---

## 7. DOM Manipulation

```javascript
// ✅ Efficient DOM updates
const fragment = document.createDocumentFragment();

items.forEach(item => {
    const li = document.createElement('li');
    li.textContent = item.name;
    fragment.appendChild(li);
});

document.querySelector('.list').appendChild(fragment);

// ✅ Remove event listeners when done
const handleClick = () => { /* ... */ };
element.addEventListener('click', handleClick);
element.removeEventListener('click', handleClick);
```

---

## 8. Error Handling

```javascript
// ✅ Try-catch in async functions
try {
    const data = await fetchData();
    processData(data);
} catch (error) {
    console.error('Fetch failed:', error);
    showUserMessage('Gagal mengambil data');
}

// ✅ Validate response
const response = await fetch(url);
if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
}
```

---

## 9. Code Organization

```javascript
// ✅ Module pattern
const StudentManager = (() => {
    // Private variables
    let students = [];
    
    // Public API
    return {
        async load() {
            students = await fetchStudents();
        },
        
        findById(id) {
            return students.find(s => s.id === id);
        },
        
        update(id, data) {
            const index = students.findIndex(s => s.id === id);
            if (index !== -1) {
                students[index] = { ...students[index], ...data };
            }
        }
    };
})();
```

---

## 10. Best Practices

### DO
- Use `const` by default, `let` when needed, never `var`
- Prefer `querySelector` over `getElementById` when convenient
- Use modern syntax (arrow functions, destructuring, etc.)
- Handle errors gracefully with try-catch
- Show loading states during async operations
- Validate input client-side AND server-side

### DON'T
- Never trust user input (validate everything)
- Don't use `eval()` or inline scripts
- Avoid manipulating `innerHTML` with user content
- Don't create global variables
- Don't forget to remove event listeners

---

## 11. TPA System JavaScript

```javascript
// Timer management
const Timer = {
    duration: 45 * 60, // 45 minutes
    remaining: 0,
    
    start() {
        this.remaining = this.duration;
        this.tick();
    },
    
    tick() {
        if (this.remaining <= 0) {
            this.onComplete();
            return;
        }
        
        this.remaining--;
        this.updateDisplay();
        setTimeout(() => this.tick(), 1000);
    },
    
    updateDisplay() {
        const minutes = Math.floor(this.remaining / 60);
        const seconds = this.remaining % 60;
        document.getElementById('timer').textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    },
    
    onComplete() {
        document.getElementById('examForm').submit();
    }
};

// Anti-back button
history.pushState(null, '', location.href);
window.addEventListener('popstate', () => {
    history.pushState(null, '', location.href);
});
```
