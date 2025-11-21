document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('uploadForm');
    const uploadZones = document.querySelectorAll('.upload-zone');
    const completeBtn = document.getElementById('complete');
    
    // 文件类型限制（改用后缀名判断）
    const allowedExtensions = {
        'imges': ['.jpg', '.jpeg', '.png', '.gif'],
        'idocx': ['.pdf', '.doc', '.docx'],
        'zfile': ['.zip', '.rar']
    };
    
    // 文件大小限制（MB）
    const maxFileSize = {
        'imges': 5,
        'idocx': 20,
        'zfile': 50
    };
    
    // 分片大小（1MB）
    const chunkSize = 1024 * 1024;
    
    // 拖拽上传
    uploadZones.forEach(zone => {
        const fileInput = zone.querySelector('.file-input');
        const preview = zone.querySelector('.file-preview');
        const progress = zone.querySelector('.progress');
        const error = zone.querySelector('.error-message');
        
        // 点击上传
        zone.addEventListener('click', () => fileInput.click());
        
        // 拖拽事件
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('dragover');
        });
        
        zone.addEventListener('dragleave', () => {
            zone.classList.remove('dragover');
        });
        
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
            
            const file = e.dataTransfer.files[0];
            if (file) handleFile(file);
        });
        
        // 文件选择
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) handleFile(file);
        });
        
        // 文件处理
        async function handleFile(file) {
            const type = zone.dataset.type;
            const atype = zone.dataset.exts;
            const extension = '.' + file.name.split('.').pop().toLowerCase();
         
            // 验证文件类型（使用后缀名）
            if (!atype || !atype.includes(extension)) {
                showError(`文件类型[${extension}]不支持!请上传${atype}格式!`); 
                return;
            }
            
            // 验证文件大小
            if (file.size > maxFileSize[type] * 1024 * 1024) {
                showError(`文件大小不能超过${maxFileSize[type]}MB`);
                return;
            }
            
            try {
                if (type === 'imges') {
                    // 图片处理
                    const compressedImage = await compressImage(file);
                    const compressedBlob = await fetch(compressedImage).then(r => r.blob());
                    
                    // 检查压缩后的大小
                    console.log('原始大小:', formatFileSize(file.size));
                    console.log('压缩后大小:', formatFileSize(compressedBlob.size));
                    
                    const formData = new FormData();
                    formData.append('imageData', compressedImage);
                    formData.append('field_name', zone.dataset.field);
                    formData.append('field_index', zone.dataset.index);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        // Update the unid data attribute
                        zone.dataset.unid = result.unid;
                        
                        preview.innerHTML = `
                            <div class="file-preview">
                                <img src="${result.file_path}" class="preview-image">
                                <div class="preview-info">
                                    <div class="preview-name">${file.name}</div>
                                </div>
                            </div>`;
                        progress.style.width = '0%';
                        error.textContent = '';
                    }
                } else {
                    // 大文件分片上传
                    const totalChunks = Math.ceil(file.size / chunkSize);
                    
                    for (let i = 0; i < totalChunks; i++) {
                        const chunk = file.slice(i * chunkSize, (i + 1) * chunkSize);
                        const formData = new FormData();
                        formData.append('chunk', chunk);
                        formData.append('field_name', zone.dataset.field);
                        formData.append('field_index', zone.dataset.index);
                        formData.append('chunk_index', i);
                        formData.append('total_chunks', totalChunks);
                        formData.append('original_name', file.name);
                        
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        progress.style.width = `${((i + 1) / totalChunks) * 100}%`;
                        
                        if (result.file_path) {
                            preview.innerHTML = `
                                <div class="file-preview">
                                    <div class="preview-info">
                                        <div class="preview-name">${file.name}</div>
                                        <div class="preview-size">${formatFileSize(file.size)}</div>
                                    </div>
                                </div>`;
                            error.textContent = '';
                        }
                    }
                }
            } catch (err) {
                showError('上传失败: ' + err.message);
                progress.style.width = '0';
            }
        }
        
        function showError(message) {
            error.textContent = message;
            error.classList.add('show');
        }
    });
    
    // 改进的图片压缩函数
    async function compressImage(file, maxWidth = 1600, quality = 0.8) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;
                    
                    // 计算压缩比例
                    if (width > maxWidth) {
                        height = (maxWidth / width) * height;
                        width = maxWidth;
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    
                    const ctx = canvas.getContext('2d');
                    // 使用更好的图像平滑算法
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    // 根据文件大小动态调整质量
                    let finalQuality = quality;
                    if (file.size > 5 * 1024 * 1024) { // 如果大于5MB
                        finalQuality = 0.6;
                    } else if (file.size > 2 * 1024 * 1024) { // 如果大于2MB
                        finalQuality = 0.7;
                    }
                    
                    resolve(canvas.toDataURL('image/jpeg', finalQuality));
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }
    
    // 文件大小格式化
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // 完成提交
    let isSubmitting = false;
    completeBtn.addEventListener('click', async function() {
        if (isSubmitting) return;
        
        isSubmitting = true;
        completeBtn.classList.add('loading');
        completeBtn.disabled = true;
        
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'complete=1'
            });
            
            const result = await response.json();
            if (result.success) {
                alert('提交成功！');
                window.location.href = 'index.php';
            } else {
                alert(result.error || '提交失败，请重试');
            }
        } catch (error) {
            console.error('提交失败:', error);
            alert('提交失败，请重试');
        }
        
        setTimeout(() => {
            isSubmitting = false;
            completeBtn.classList.remove('loading');
            completeBtn.disabled = false;
        }, 5000);
    });
}); 