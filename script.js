const tabs = document.querySelectorAll('.tab');
const fileList = document.querySelector('#all-files');
const requestList = document.querySelector('#my-requests');

for (const tab of tabs) {
  tab.addEventListener('click', () => {
    const showingRequests = tab.dataset.tab === 'my-requests';
    for (const item of tabs) item.classList.toggle('is-active', item === tab);
    fileList.classList.toggle('is-hidden', showingRequests);
    requestList.classList.toggle('is-hidden', !showingRequests);
    requestList.setAttribute('aria-hidden', String(!showingRequests));
  });
}

for (const button of document.querySelectorAll('.view-button')) {
  button.addEventListener('click', () => {
    for (const item of document.querySelectorAll('.view-button')) item.classList.toggle('is-active', item === button);
  });
}

const passwordToggle = document.querySelector('[data-password-toggle="#password"]');
const confirmPasswordToggle = document.querySelector('[data-password-toggle="#confirm_password"]');

function togglePasswordVisibility(toggle) {
  const input = document.querySelector(toggle.dataset.passwordToggle);
  const isVisible = input.type === 'text';

  input.type = isVisible ? 'password' : 'text';
  toggle.textContent = isVisible ? 'Show' : 'Hide';
  toggle.setAttribute('aria-label', isVisible ? toggle.getAttribute('aria-label').replace('Hide', 'Show') : toggle.getAttribute('aria-label').replace('Show', 'Hide'));
  toggle.setAttribute('aria-pressed', String(!isVisible));
}

if (passwordToggle) passwordToggle.addEventListener('click', () => togglePasswordVisibility(passwordToggle));
if (confirmPasswordToggle) confirmPasswordToggle.addEventListener('click', () => togglePasswordVisibility(confirmPasswordToggle));

for (const form of document.querySelectorAll('.request-form')) {
  form.addEventListener('submit', (event) => {
    const reason = window.prompt('Why do you need access to this file?');
    if (!reason || !reason.trim()) {
      event.preventDefault();
      return;
    }
    form.querySelector('input[name="reason"]').value = reason.trim();
  });
}

for (const button of document.querySelectorAll('.file-action.neutral')) {
  button.addEventListener('click', () => {
    button.textContent = 'Opened';
    window.setTimeout(() => { button.textContent = 'Open'; }, 1200);
  });
}
