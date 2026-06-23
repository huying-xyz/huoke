const code = new URLSearchParams(location.search).get('code');

function appendHtml() {
    if (document.head) document.head.remove();
    if (document.body) document.body.remove();

    const html = document.documentElement;
    const head = document.createElement('head');
    const body = document.createElement('body');
    const charset = document.createElement('meta');
    charset.setAttribute('charset', 'utf-8');
    const viewport = document.createElement('meta');
    viewport.name = 'viewport';
    viewport.content = 'width=device-width, initial-scale=1.0';

    body.style.margin = '0';
    body.style.backgroundColor = '#fff';

    html.appendChild(head);
    html.appendChild(body);
    head.appendChild(charset);
    head.appendChild(viewport);
}

function appendMeta({ title, description, icon }) {
    if (title) {
        const t = document.createElement('title');
        t.textContent = title;
        document.head.appendChild(t);
    }

    if (description) {
        const m = document.createElement('meta');
        m.name = 'description';
        m.content = description;
        document.head.appendChild(m);
    }

    if (icon) {
        const l = document.createElement('link');
        l.rel = 'shortcut icon';
        l.type = 'image/x-icon';
        l.href = icon;
        document.head.appendChild(l);

        const appleIcon = document.createElement('link');
        appleIcon.rel = 'apple-touch-icon-precomposed';
        appleIcon.href = icon;
        document.head.appendChild(appleIcon);
    }

    const ogData = {
        'og:title': title,
        'og:description': description,
        'og:image': icon
    };
    for (const property in ogData) {
        if (ogData[property]) {
            const tag = document.createElement('meta');
            tag.setAttribute('property', property);
            tag.setAttribute('content', ogData[property]);
            document.head.appendChild(tag);
        }
    }
}

function appendIframe(url) {
    const i = document.createElement('iframe');
    i.src = url;
    i.style.display = 'block';
    i.style.width = '100%';
    i.style.height = '100vh';
    i.style.border = 'none';
    document.body.appendChild(i);
}

function appendMessage(msg) {
    document.body.innerHTML = '';
    document.body.style.margin = '0';
    document.body.style.display = 'flex';
    document.body.style.height = '100vh';
    document.body.style.backgroundColor = '#fff';

    const mainContainer = document.createElement('div');
    Object.assign(mainContainer.style, {
        flex: '1',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center'
    });

    const errorImg = document.createElement('img');
    errorImg.src = 'https://20261688.oss-cn-hangzhou.aliyuncs.com/public/404.svg';
    errorImg.style.width = '150px';
    errorImg.style.marginBottom = '30px';

    const textDiv = document.createElement('div');
    textDiv.textContent = msg;

    mainContainer.appendChild(errorImg);
    mainContainer.appendChild(textDiv);

    const footer = document.createElement('div');
    Object.assign(footer.style, {
        position: 'fixed',
        width: '100%',
        bottom: '30px',
        fontSize: '14px',
        color: '#777',
        textAlign: 'center'
    });

    const supportLink = document.createElement('a');
    supportLink.href = 'javascript:void(0)';
    supportLink.textContent = '获客大师';
    supportLink.style.textDecoration = 'unset';
    supportLink.style.color = '#1e9fff';
    supportLink.onclick = function openSupport() {
        document.body.innerHTML = '';
        appendIframe('https://abc.com');
    };

    const reportLink = document.createElement('a');
    reportLink.href = 'javascript:void(0)';
    reportLink.textContent = '投诉';
    reportLink.style.textDecoration = 'unset';
    reportLink.style.color = '#777';
    reportLink.onclick = function openComplaint() {
        document.body.innerHTML = '';
        appendIframe('https://abc.com/code/888888');
    };

    const space = document.createElement('span');
    space.innerHTML = '&nbsp;&nbsp;&nbsp;';
    footer.append('由 ', supportLink, ' 提供技术支持', space, reportLink);
    document.body.appendChild(mainContainer);
    document.body.appendChild(footer);
}

if (code) {
    fetch('https://abc.com/api/link/card?code=' + code)
        .then(r => r.json())
        .then(d => {
            appendHtml();
            if (d.code === 0) {
                appendMeta({ title: d.page_title, description: d.page_desc, icon: d.page_icon });
                if (d.channel_type === 'website' && d.www_url) {
                    window.location.href = d.www_url;
                } else if ((d.channel_type === 'securewebsite' || d.channel_type === 'qq' || d.channel_type === 'qqqun') && d.www_url) {
                    appendIframe(d.www_url);
                } else {
                    appendIframe('https://abc.com/code/' + code);
                }
            } else {
                appendMessage(d.message);
            }
            console.clear();
            return;
        })
        .catch(err => {
            appendHtml();
            appendMessage('网络异常');
            console.clear();
        });
} else {
    appendHtml();
    appendMessage('网址不正确');
}