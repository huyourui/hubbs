/**
 * HuBBS Editor - Markdown + WYSIWYG 双模式编辑器
 * 简洁、高效、易用
 */

(function(global) {
    'use strict';

    // Markdown 解析器（简化版）
    const MarkdownParser = {
        parse(text) {
            if (!text) return '';
            
            let html = text
                // 转义 HTML
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                // 代码块
                .replace(/```(\w+)?\n([\s\S]*?)```/g, '<pre><code class="language-$1">$2</code></pre>')
                // 行内代码
                .replace(/`([^`]+)`/g, '<code>$1</code>')
                // 标题
                .replace(/^### (.*$)/gim, '<h3>$1</h3>')
                .replace(/^## (.*$)/gim, '<h2>$1</h2>')
                .replace(/^# (.*$)/gim, '<h1>$1</h1>')
                // 粗体
                .replace(/\*\*([^\*]+)\*\*/g, '<strong>$1</strong>')
                .replace(/__([^_]+)__/g, '<strong>$1</strong>')
                // 斜体
                .replace(/\*([^\*]+)\*/g, '<em>$1</em>')
                .replace(/_([^_]+)_/g, '<em>$1</em>')
                // 删除线
                .replace(/~~([^~]+)~~/g, '<del>$1</del>')
                // 引用
                .replace(/^> (.*$)/gim, '<blockquote>$1</blockquote>')
                // 无序列表
                .replace(/^\- (.*$)/gim, '<ul><li>$1</li></ul>')
                .replace(/^\* (.*$)/gim, '<ul><li>$1</li></ul>')
                // 有序列表
                .replace(/^\d+\. (.*$)/gim, '<ol><li>$1</li></ol>')
                // 链接
                .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
                // 图片
                .replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img src="$2" alt="$1" />')
                // 水平线
                .replace(/^---$/gim, '<hr />')
                .replace(/^\*\*\*$/gim, '<hr />')
                // 表格
                .replace(/\|(.+)\|\n\|[-:\|]+\|\n((?:\|.+\|\n?)+)/g, (match, header, rows) => {
                    const headers = header.split('|').map(h => h.trim()).filter(h => h);
                    const rowData = rows.trim().split('\n').map(row => 
                        row.split('|').map(c => c.trim()).filter(c => c)
                    );
                    let table = '<table><thead><tr>';
                    headers.forEach(h => table += `<th>${h}</th>`);
                    table += '</tr></thead><tbody>';
                    rowData.forEach(row => {
                        table += '<tr>';
                        row.forEach(c => table += `<td>${c}</td>`);
                        table += '</tr>';
                    });
                    table += '</tbody></table>';
                    return table;
                })
                // 段落
                .replace(/\n\n/g, '</p><p>')
                // 换行
                .replace(/\n/g, '<br>');
            
            // 包装在段落中
            if (!html.startsWith('<')) {
                html = '<p>' + html + '</p>';
            }
            
            // 合并相邻的列表
            html = html.replace(/<\/ul>\s*<ul>/g, '')
                      .replace(/<\/ol>\s*<ol>/g, '')
                      .replace(/<\/blockquote>\s*<blockquote>/g, '<br>');
            
            return html;
        }
    };

    // HTML 转 Markdown
    const HtmlToMarkdown = {
        convert(html) {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            let md = this.parseBlockElements(temp);
            // 清理多余的换行
            md = md.replace(/\n{3,}/g, '\n\n');
            return md.trim();
        },
        
        // 解析块级元素（直接子元素）
        parseBlockElements(node) {
            let md = '';
            
            node.childNodes.forEach(child => {
                if (child.nodeType === Node.TEXT_NODE) {
                    const text = child.textContent.trim();
                    if (text) {
                        md += text + '\n\n';
                    }
                } else if (child.nodeType === Node.ELEMENT_NODE) {
                    md += this.parseBlockElement(child);
                }
            });
            
            return md;
        },
        
        // 解析单个块级元素
        parseBlockElement(el) {
            const tag = el.tagName.toLowerCase();
            const content = this.parseInlineElements(el);
            
            switch (tag) {
                case 'h1': return `# ${content}\n\n`;
                case 'h2': return `## ${content}\n\n`;
                case 'h3': return `### ${content}\n\n`;
                case 'h4': return `#### ${content}\n\n`;
                case 'h5': return `##### ${content}\n\n`;
                case 'h6': return `###### ${content}\n\n`;
                case 'p': return `${content}\n\n`;
                case 'div': 
                    // 处理 <div><br></div> 为空段落
                    if (content === '' || content === '\n') {
                        return '\n\n';
                    }
                    return `${content}\n\n`;
                case 'pre': 
                    const code = el.querySelector('code');
                    const lang = code ? (code.className.replace('language-', '') || '') : '';
                    const codeText = code ? code.textContent : el.textContent;
                    return `\`\`\`${lang}\n${codeText}\n\`\`\`\n\n`;
                case 'blockquote': 
                    return `> ${content.replace(/\n/g, '\n> ')}\n\n`;
                case 'ul':
                    return this.parseList(el, 'ul');
                case 'ol':
                    return this.parseList(el, 'ol');
                case 'hr': 
                    return `---\n\n`;
                case 'br': 
                    return '\n';
                default: 
                    // 其他元素作为行内处理
                    return content + '\n\n';
            }
        },
        
        // 解析行内元素
        parseInlineElements(node) {
            let md = '';
            
            node.childNodes.forEach(child => {
                if (child.nodeType === Node.TEXT_NODE) {
                    md += child.textContent;
                } else if (child.nodeType === Node.ELEMENT_NODE) {
                    md += this.parseInlineElement(child);
                }
            });
            
            return md;
        },
        
        // 解析单个行内元素
        parseInlineElement(el) {
            const tag = el.tagName.toLowerCase();
            const content = this.parseInlineElements(el);
            
            switch (tag) {
                case 'strong':
                case 'b': return `**${content}**`;
                case 'em':
                case 'i': return `*${content}*`;
                case 'del':
                case 's': return `~~${content}~~`;
                case 'code': return `\`${content}\``;
                case 'a': {
                    let href = el.getAttribute('href') || '';
                    // 解码 URL，避免双重编码
                    try {
                        href = decodeURIComponent(href);
                    } catch (e) {
                        // 如果解码失败，使用原始值
                    }
                    // 转义 Markdown 中的特殊字符
                    href = href.replace(/\(/g, '%28').replace(/\)/g, '%29');
                    return `[${content}](${href})`;
                }
                case 'img': 
                    const src = el.getAttribute('src') || '';
                    const alt = el.getAttribute('alt') || '';
                    return `![${alt}](${src})`;
                case 'br': return '\n';
                default: return content;
            }
        },
        
        // 解析列表
        parseList(el, type) {
            let md = '';
            let index = 1;
            
            el.childNodes.forEach(child => {
                if (child.nodeType === Node.ELEMENT_NODE && child.tagName.toLowerCase() === 'li') {
                    const content = this.parseInlineElements(child).trim();
                    if (type === 'ol') {
                        md += `${index}. ${content}\n`;
                        index++;
                    } else {
                        md += `- ${content}\n`;
                    }
                }
            });
            
            return md + '\n';
        }
    };

    // 编辑器类
    class HubbsEditor {
        constructor(element, options = {}) {
            this.container = typeof element === 'string' ? document.querySelector(element) : element;
            this.options = Object.assign({
                placeholder: '请输入内容...',
                height: 300,
                mode: 'wysiwyg', // 'markdown', 'wysiwyg', 'split'
                onChange: null
            }, options);
            
            this.currentMode = this.options.mode;
            this.value = '';
            
            this.init();
        }
        
        init() {
            this.createDOM();
            this.bindEvents();
        }
        
        createDOM() {
            this.container.className = 'hubbs-editor';
            this.container.innerHTML = `
                <div class="editor-toolbar">
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" data-action="bold" title="粗体" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
                        </button>
                        <button type="button" class="toolbar-btn" data-action="italic" title="斜体" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/></svg>
                        </button>
                        <button type="button" class="toolbar-btn" data-action="strike" title="删除线" tabindex="-1">
                            <svg viewBox="0 0 24 24"><text x="12" y="18" text-anchor="middle" fill="currentColor" font-size="18" font-weight="600">S</text><line x1="3" y1="12" x2="21" y2="12" stroke="currentColor" stroke-width="2"/></svg>
                        </button>
                    </div>
                    <div class="toolbar-group">
                        <div class="toolbar-dropdown" data-action="fontSize" title="字号">
                            <button type="button" class="toolbar-btn toolbar-dropdown-btn" tabindex="-1">
                                <svg viewBox="0 0 24 24"><text x="6" y="17" fill="currentColor" font-size="14" font-weight="600">A</text><text x="14" y="12" fill="currentColor" font-size="10">▼</text></svg>
                            </button>
                            <div class="toolbar-dropdown-menu">
                                <div class="toolbar-dropdown-item" data-value="1"><span style="font-size:10px">小</span></div>
                                <div class="toolbar-dropdown-item" data-value="2"><span style="font-size:13px">正常</span></div>
                                <div class="toolbar-dropdown-item" data-value="3"><span style="font-size:16px">大</span></div>
                                <div class="toolbar-dropdown-item" data-value="4"><span style="font-size:18px">特大</span></div>
                                <div class="toolbar-dropdown-item" data-value="5"><span style="font-size:24px">超大</span></div>
                                <div class="toolbar-dropdown-item" data-value="6"><span style="font-size:32px">巨大</span></div>
                                <div class="toolbar-dropdown-item" data-value="7"><span style="font-size:40px">极大</span></div>
                            </div>
                        </div>
                        <div class="toolbar-color-wrapper" title="字体颜色">
                            <button type="button" class="toolbar-btn toolbar-color-btn" tabindex="-1">
                                <svg viewBox="0 0 24 24"><text x="6" y="17" fill="currentColor" font-size="14" font-weight="600">A</text><line x1="6" y1="20" x2="16" y2="20" stroke="currentColor" stroke-width="2"/></svg>
                            </button>
                            <input type="color" class="toolbar-color-input" data-action="foreColor" value="#000000">
                        </div>
                        <div class="toolbar-color-wrapper" title="背景色">
                            <button type="button" class="toolbar-btn toolbar-color-btn" tabindex="-1">
                                <svg viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" fill="none" stroke="currentColor" stroke-width="1.5" rx="2"/><text x="12" y="15" text-anchor="middle" fill="currentColor" font-size="10" font-weight="500">BG</text></svg>
                            </button>
                            <input type="color" class="toolbar-color-input" data-action="backColor" value="#ffff00">
                        </div>
                    </div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" data-action="h1" title="标题1" tabindex="-1">H1</button>
                        <button type="button" class="toolbar-btn" data-action="h2" title="标题2" tabindex="-1">H2</button>
                        <button type="button" class="toolbar-btn" data-action="h3" title="标题3" tabindex="-1">H3</button>
                    </div>
                    <div class="toolbar-group">
                        <div class="toolbar-dropdown" data-action="align" title="对齐方式">
                            <button type="button" class="toolbar-btn toolbar-dropdown-btn" tabindex="-1">
                                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M15 15H3v2h12v-2zm0-8H3v2h12V7zM3 13h18v-2H3v2zm0 8h18v-2H3v2zM3 3v2h18V3H3z"/></svg>
                            </button>
                            <div class="toolbar-dropdown-menu toolbar-align-menu">
                                <div class="toolbar-dropdown-item" data-value="justifyLeft">
                                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M15 15H3v2h12v-2zm0-8H3v2h12V7zM3 13h18v-2H3v2zm0 8h18v-2H3v2zM3 3v2h18V3H3z"/></svg>
                                    <span>左对齐</span>
                                </div>
                                <div class="toolbar-dropdown-item" data-value="justifyCenter">
                                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"/></svg>
                                    <span>居中对齐</span>
                                </div>
                                <div class="toolbar-dropdown-item" data-value="justifyRight">
                                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M3 15h12v2H3v-2zm14 0h4v2h-4v-2zM3 7h12v2H3V7zm14 0h4v2h-4V7zM3 11h18v2H3v-2zm0 8h18v2H3v-2zM3 3h18v2H3V3z"/></svg>
                                    <span>右对齐</span>
                                </div>
                                <div class="toolbar-dropdown-item" data-value="justifyFull">
                                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M3 15h18v2H3v-2zm0-8h18v2H3V7zm0 4h18v2H3v-2zm0 8h18v2H3v-2zm0-16h18v2H3V3z"/></svg>
                                    <span>两端对齐</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" data-action="ul" title="无序列表" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>
                        </button>
                        <button type="button" class="toolbar-btn" data-action="ol" title="有序列表" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>
                        </button>
                        <button type="button" class="toolbar-btn" data-action="quote" title="引用" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>
                        </button>
                        <button type="button" class="toolbar-btn" data-action="code" title="代码" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>
                        </button>
                    </div>
                    <div class="toolbar-group">
                        <button type="button" class="toolbar-btn" data-action="link" title="链接" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
                        </button>
                        <button type="button" class="toolbar-btn" data-action="image" title="图片" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                        </button>
                        <button type="button" class="toolbar-btn" data-action="attachment" title="附件" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5c0-1.38 1.12-2.5 2.5-2.5s2.5 1.12 2.5 2.5v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/></svg>
                        </button>
                        <button type="button" class="toolbar-btn" data-action="hr" title="分隔线" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M19 13H5v-2h14v2z"/></svg>
                        </button>
                    </div>
                </div>
                <div class="editor-body">
                    <div class="editor-pane editor-wysiwyg" contenteditable="true" placeholder="${this.options.placeholder}"></div>
                </div>
            `;
            
            this.wysiwyg = this.container.querySelector('.editor-wysiwyg');
        }

        bindEvents() {
            // 工具栏按钮
            this.container.querySelectorAll('.toolbar-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const action = btn.dataset.action;
                    if (action) this.execCommand(action, btn);
                });
            });

            // 工具栏下拉菜单
            this.container.querySelectorAll('.toolbar-dropdown').forEach(dropdown => {
                const btn = dropdown.querySelector('.toolbar-dropdown-btn');
                const menu = dropdown.querySelector('.toolbar-dropdown-menu');
                
                // 点击按钮切换下拉菜单
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // 关闭其他下拉菜单
                    this.container.querySelectorAll('.toolbar-dropdown-menu.active').forEach(m => {
                        if (m !== menu) m.classList.remove('active');
                    });
                    menu.classList.toggle('active');
                });
                
                // 点击菜单项
                menu.querySelectorAll('.toolbar-dropdown-item').forEach(item => {
                    item.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const action = dropdown.dataset.action;
                        const value = item.dataset.value;
                        if (action && value) {
                            // 对齐菜单直接执行对齐命令
                            if (action === 'align') {
                                this.execCommand(value, null, null);
                            } else {
                                this.execCommand(action, null, value);
                            }
                        }
                        menu.classList.remove('active');
                    });
                });
            });

            // 点击其他地方关闭下拉菜单
            document.addEventListener('click', () => {
                this.container.querySelectorAll('.toolbar-dropdown-menu.active').forEach(menu => {
                    menu.classList.remove('active');
                });
            });

            // 工具栏颜色选择器
            this.container.querySelectorAll('.toolbar-color-wrapper').forEach(wrapper => {
                const btn = wrapper.querySelector('.toolbar-color-btn');
                const input = wrapper.querySelector('.toolbar-color-input');
                
                // 点击按钮触发颜色选择
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    input.click();
                });
                
                // 颜色选择变化
                input.addEventListener('input', (e) => {
                    const action = input.dataset.action;
                    const value = input.value;
                    if (action && value) {
                        this.execCommand(action, null, value);
                    }
                });
            });

            // WYSIWYG 模式下监听选区变化，更新按钮状态
            if (this.wysiwyg) {
                this.wysiwyg.addEventListener('keyup', () => this.updateToolbarState());
                this.wysiwyg.addEventListener('mouseup', () => this.updateToolbarState());
                this.wysiwyg.addEventListener('click', () => this.updateToolbarState());
            }

            // WYSIWYG 输入 - 直接保存 HTML
            this.wysiwyg.addEventListener('input', () => {
                this.value = this.wysiwyg.innerHTML;
                this.triggerChange();
            });

            // Tab 键支持
            this.wysiwyg.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    this.insertText('    ');
                }
            });

            // Ctrl+Enter 快捷键支持
            this.wysiwyg.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === 'Enter') {
                    // 触发自定义事件，让外部处理提交
                    const submitEvent = new CustomEvent('editorSubmit', {
                        bubbles: true,
                        cancelable: true
                    });
                    this.container.dispatchEvent(submitEvent);
                }
            });

            // 处理引用块内的回车键 - 在空引用行退出引用
            this.wysiwyg.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    const selection = window.getSelection();
                    if (selection.rangeCount === 0) return;

                    const range = selection.getRangeAt(0);
                    let currentNode = range.commonAncestorContainer;
                    if (currentNode.nodeType === Node.TEXT_NODE) {
                        currentNode = currentNode.parentElement;
                    }

                    // 检查是否在引用块内
                    const blockquote = currentNode.closest ? currentNode.closest('blockquote') : null;
                    if (!blockquote) return;

                    // 获取当前行内容
                    let currentBlock = currentNode;
                    while (currentBlock && currentBlock.parentElement !== blockquote) {
                        currentBlock = currentBlock.parentElement;
                    }

                    // 如果当前行为空或只有换行符，退出引用
                    const textContent = currentBlock ? currentBlock.textContent.trim() : '';
                    if (textContent === '' || textContent === '\n') {
                        e.preventDefault();
                        this.exitQuote(blockquote);
                    }
                }
            });

        }
        
        execCommand(action, btn, value = null) {
            // 特殊处理图片和附件上传
            if (action === 'image') {
                this.uploadImage();
                return;
            }
            if (action === 'attachment') {
                this.uploadAttachment();
                return;
            }

            // 需要值的命令（如字号、颜色）直接执行
            if (['fontSize', 'foreColor', 'backColor'].includes(action)) {
                if (this.currentMode === 'wysiwyg') {
                    this.execWysiwygCommand(action, value);
                }
                return;
            }

            // 对齐命令直接执行
            if (['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'].includes(action)) {
                if (this.currentMode === 'wysiwyg') {
                    this.execWysiwygCommand(action, value);
                }
                return;
            }

            const commands = {
                bold: { md: '**', text: '粗体' },
                italic: { md: '*', text: '斜体' },
                strike: { md: '~~', text: '删除线' },
                code: { md: '`', text: '代码' },
                h1: { md: '# ', text: '标题' },
                h2: { md: '## ', text: '标题' },
                h3: { md: '### ', text: '标题' },
                ul: { md: '- ', text: '列表项' },
                ol: { md: '1. ', text: '列表项' },
                quote: { md: '> ', text: '引用' },
                hr: { md: '\n---\n', text: '' },
                link: { md: '[链接文本](url)', text: '' }
            };

            const cmd = commands[action];
            if (!cmd) return;

            if (this.currentMode === 'wysiwyg') {
                this.execWysiwygCommand(action, value);
            } else {
                this.insertMarkdown(cmd.md, cmd.text);
            }
        }

        /**
         * 上传图片
         */
        uploadImage() {
            // 创建文件选择器
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = (e) => {
                const file = e.target.files[0];
                if (!file) return;

                // 显示上传中提示
                this.showUploadToast('图片上传中...');

                // 创建 FormData
                const formData = new FormData();
                formData.append('file', file);

                // 发送上传请求
                fetch('upload.php?action=image', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    this.hideUploadToast();
                    if (data.success) {
                        this.insertImage(data.data.url, data.data.name);
                        this.showUploadToast('图片上传成功', 'success');
                    } else {
                        this.showUploadToast(data.message || '上传失败', 'error');
                    }
                })
                .catch(err => {
                    this.hideUploadToast();
                    this.showUploadToast('上传失败: ' + err.message, 'error');
                });
            };
            input.click();
        }

        /**
         * 上传附件
         */
        uploadAttachment() {
            const input = document.createElement('input');
            input.type = 'file';
            input.onchange = (e) => {
                const file = e.target.files[0];
                if (!file) return;

                this.showUploadToast('附件上传中...');

                const formData = new FormData();
                formData.append('file', file);

                fetch('upload.php?action=attachment', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    this.hideUploadToast();
                    if (data.success) {
                        this.insertAttachment(data.data.url, data.data.name, data.data.ext);
                        this.showUploadToast('附件上传成功', 'success');
                    } else {
                        this.showUploadToast(data.message || '上传失败', 'error');
                    }
                })
                .catch(err => {
                    this.hideUploadToast();
                    this.showUploadToast('上传失败: ' + err.message, 'error');
                });
            };
            input.click();
        }

        /**
         * 插入图片到编辑器
         */
        insertImage(url, alt) {
            if (this.currentMode === 'wysiwyg') {
                this.wysiwyg.focus();
                document.execCommand('insertImage', false, url);
            } else {
                const markdown = `![${alt || '图片'}](${url})`;
                this.insertText(markdown);
            }
        }

        /**
         * 插入附件到编辑器
         */
        insertAttachment(url, name, ext) {
            const linkText = `[附件: ${name}]`;
            if (this.currentMode === 'wysiwyg') {
                this.wysiwyg.focus();
                const html = `<a href="${url}" target="_blank" rel="noopener" class="attachment-link">${linkText}</a>`;
                document.execCommand('insertHTML', false, html);
            } else {
                const markdown = `[${linkText}](${url})`;
                this.insertText(markdown);
            }
        }

        /**
         * 显示上传提示
         */
        showUploadToast(message, type = 'info') {
            // 移除已有的 toast
            this.hideUploadToast();

            const toast = document.createElement('div');
            toast.className = `editor-toast editor-toast-${type}`;
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                padding: 10px 20px;
                background: ${type === 'error' ? '#ff4d4f' : type === 'success' ? '#52c41a' : '#1890ff'};
                color: #fff;
                border-radius: 4px;
                z-index: 10000;
                font-size: 14px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            `;
            document.body.appendChild(toast);

            if (type !== 'info') {
                setTimeout(() => this.hideUploadToast(), 3000);
            }
        }

        /**
         * 隐藏上传提示
         */
        hideUploadToast() {
            const toast = document.querySelector('.editor-toast');
            if (toast) toast.remove();
        }
        
        // 更新工具栏按钮状态
        updateToolbarState() {
            if (this.currentMode !== 'wysiwyg') return;
            
            const stateMap = {
                bold: 'bold',
                italic: 'italic',
                strike: 'strikeThrough',
                ul: 'insertUnorderedList',
                ol: 'insertOrderedList',
                h1: 'formatBlock:H1',
                h2: 'formatBlock:H2',
                h3: 'formatBlock:H3',
                quote: 'formatBlock:BLOCKQUOTE',
                justifyLeft: 'justifyLeft',
                justifyCenter: 'justifyCenter',
                justifyRight: 'justifyRight',
                justifyFull: 'justifyFull'
            };
            
            this.container.querySelectorAll('.toolbar-btn').forEach(btn => {
                const action = btn.dataset.action;
                if (!action || !stateMap[action]) return;
                
                const state = stateMap[action];
                let isActive = false;
                
                if (state.startsWith('formatBlock:')) {
                    const tag = state.split(':')[1];
                    isActive = document.queryCommandValue('formatBlock') === tag;
                } else {
                    isActive = document.queryCommandState(state);
                }
                
                btn.classList.toggle('active', isActive);
            });
        }
        
        execWysiwygCommand(action, value = null) {
            document.execCommand('styleWithCSS', false, true);
            
            const commands = {
                bold: () => document.execCommand('bold'),
                italic: () => document.execCommand('italic'),
                strike: () => document.execCommand('strikeThrough'),
                h1: () => document.execCommand('formatBlock', false, 'H1'),
                h2: () => document.execCommand('formatBlock', false, 'H2'),
                h3: () => document.execCommand('formatBlock', false, 'H3'),
                ul: () => document.execCommand('insertUnorderedList'),
                ol: () => document.execCommand('insertOrderedList'),
                quote: () => this.toggleQuote(),
                fontSize: (size) => document.execCommand('fontSize', false, size),
                foreColor: (color) => document.execCommand('foreColor', false, color),
                backColor: (color) => document.execCommand('hiliteColor', false, color) || document.execCommand('backColor', false, color),
                justifyLeft: () => document.execCommand('justifyLeft'),
                justifyCenter: () => document.execCommand('justifyCenter'),
                justifyRight: () => document.execCommand('justifyRight'),
                justifyFull: () => document.execCommand('justifyFull'),
                code: () => {
                    const selection = window.getSelection();
                    if (selection.toString()) {
                        document.execCommand('insertHTML', false, `<code>${selection.toString()}</code>`);
                    }
                },
                link: () => {
                    const url = prompt('请输入链接地址:', 'https://');
                    if (url) {
                        const selection = window.getSelection();
                        const selectedText = selection.toString() || url;
                        document.execCommand('insertHTML', false, `<a href="${url}" target="_blank" rel="noopener">${selectedText}</a>`);
                    }
                },
                image: () => {
                    const url = prompt('请输入图片地址:', 'https://');
                    if (url) document.execCommand('insertImage', false, url);
                },
                hr: () => document.execCommand('insertHorizontalRule')
            };
            
            this.wysiwyg.focus();
            if (commands[action]) {
                if (value !== null) {
                    commands[action](value);
                } else {
                    commands[action]();
                }
            }

            // 更新工具栏状态
            this.updateToolbarState();

            // 更新值 - execCommand 不会触发 input 事件，需要手动更新
            setTimeout(() => {
                this.value = this.wysiwyg.innerHTML;
                this.triggerChange();
            }, 0);
        }

        insertText(text) {
            document.execCommand('insertText', false, text);
            // execCommand 不会触发 input 事件，需要手动更新
            setTimeout(() => {
                this.value = this.wysiwyg.innerHTML;
                this.triggerChange();
            }, 0);
        }

        // 切换引用状态
        toggleQuote() {
            const selection = window.getSelection();
            const range = selection.getRangeAt(0);
            
            // 检查当前是否在引用块内
            let currentNode = range.commonAncestorContainer;
            if (currentNode.nodeType === Node.TEXT_NODE) {
                currentNode = currentNode.parentElement;
            }
            
            const blockquote = currentNode.closest ? currentNode.closest('blockquote') : null;
            
            if (blockquote) {
                // 当前在引用块内，退出引用
                this.exitQuote(blockquote);
            } else {
                // 当前不在引用块内，创建引用
                document.execCommand('formatBlock', false, 'BLOCKQUOTE');
            }
        }

        // 退出引用块
        exitQuote(blockquote) {
            const selection = window.getSelection();
            const range = selection.getRangeAt(0);
            
            // 获取当前光标位置
            let currentNode = range.commonAncestorContainer;
            if (currentNode.nodeType === Node.TEXT_NODE) {
                currentNode = currentNode.parentElement;
            }
            
            // 找到当前所在的段落
            let currentBlock = currentNode;
            while (currentBlock && currentBlock.parentElement !== blockquote) {
                currentBlock = currentBlock.parentElement;
            }
            
            if (!currentBlock) {
                currentBlock = blockquote.lastElementChild || blockquote;
            }
            
            // 创建新的段落并移动到引用块后面
            const newParagraph = document.createElement('p');
            newParagraph.innerHTML = '<br>';
            
            // 在当前块后面插入新段落
            if (currentBlock.nextSibling) {
                blockquote.parentElement.insertBefore(newParagraph, currentBlock.nextSibling);
            } else {
                blockquote.parentElement.appendChild(newParagraph);
            }
            
            // 将光标移动到新段落
            range.selectNodeContents(newParagraph);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
        }
        
        triggerChange() {
            if (typeof this.options.onChange === 'function') {
                this.options.onChange(this.value);
            }
        }
        
        // 公共 API
        getValue() {
            return this.value;
        }
        
        setValue(value) {
            this.value = value || '';
            this.wysiwyg.innerHTML = this.value;
        }
        
        getHTML() {
            return MarkdownParser.parse(this.value);
        }
        
        insertMarkdownText(text) {
            this.insertText(text);
        }
        
        focus() {
            const el = this.currentMode === 'wysiwyg' ? this.wysiwyg : this.textarea;
            el.focus();
        }
    }
    
    // 导出
    global.HubbsEditor = HubbsEditor;
    
})(window);
