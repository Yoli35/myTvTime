import { Notyf } from 'notyf';
import 'notyf/notyf.min.css'; // for React, Vue and Svelte

// Create an instance of Notyf
const notyf = new Notyf({
    duration: 50000,
    position: {
        x: "right",
        y: "top"
    },
    types: [
        {
            type: "info",
            background: '#0D9488',
            icon: false
        },
        {
            type: "warning",
            background: '#ad5d02',
            icon: false
        },
    ]
});

let notyf_messages = document.querySelectorAll('#notyf-message');

notyf_messages.forEach(message => {
    if (message.className === 'success') {
        notyf.success(message.innerHTML);
    }

    if (message.className === 'error') {
        notyf.error(message.innerHTML);
    }

    if (message.className === 'info') {
        notyf.open({
            type: 'info',
            message: '<b>Info</b> - ' + message.innerHTML,
        });
    }

    if (message.className === 'warning') {
        notyf.open({
            type: 'warning',
            message: '<b>Warning</b> - ' + message.innerHTML
        });
    }
});
