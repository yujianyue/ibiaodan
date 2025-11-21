document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signupForm');
    const inputs = form.querySelectorAll('input[data-pattern]');
    
    // 表单验证
    inputs.forEach(input => {
        const errorMessage = input.closest('.form-group').querySelector('.error-message');
        const validationIcon = input.closest('.input-wrapper').querySelector('.validation-icon');
        
        // 输入时验证
        let debounceTimer;
        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => validate(this), 500);
        });
        
        // 失去焦点时验证
        input.addEventListener('blur', function() {
            validate(this);
        });
        
        async function validate(input) {
            const pattern = input.dataset.pattern;
            const value = input.value;
            
            if (!value) {
                showError('此项不能为空');
                return;
            }
            
            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=validate&field=${input.name}&value=${value}&pattern=${pattern}`
                });
                
                const result = await response.json();
                if (!result.valid) {
                    showError('格式不正确');
                    input.classList.add('invalid');
                    if (validationIcon) {
                        validationIcon.innerHTML = '✕';
                        validationIcon.style.color = 'var(--error-color)';
                    }
                } else {
                    hideError();
                    input.classList.remove('invalid');
                    if (validationIcon) {
                        validationIcon.innerHTML = '✓';
                        validationIcon.style.color = 'var(--success-color)';
                    }
                }
            } catch (error) {
                console.error('验证出错:', error);
                showError('验证失败，请重试');
            }
        }
        
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.classList.add('show');
        }
        
        function hideError() {
            errorMessage.textContent = '';
            errorMessage.classList.remove('show');
        }
    });
    
    // 表单提交
    form.addEventListener('submit', function(e) {
        const submitBtn = form.querySelector('.btn-submit');
        const invalidInputs = form.querySelectorAll('input.invalid');
        
        if (invalidInputs.length > 0) {
            e.preventDefault();
            alert('请修正表单中的错误后再提交');
            return;
        }
        
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
    });
}); 