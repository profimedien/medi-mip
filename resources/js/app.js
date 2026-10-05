// Service Worker für PWA (App-Installation und Push)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

function urlBase64ToUint8Array(base64) {
    const padding = '='.repeat((4 - (base64.length % 4)) % 4);
    const raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

// Push-Mitteilungen ein- und ausschalten (aufgerufen aus dem Profil)
window.mipPush = {
    supported() {
        return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    },
    async status() {
        if (!this.supported()) return 'unsupported';
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        return sub ? 'on' : 'off';
    },
    async enable(vapidKey) {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return 'denied';
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey),
        });
        await fetch('/push/abonnieren', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify(sub.toJSON()),
        });
        return 'on';
    },
    async disable() {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        if (sub) {
            await fetch('/push/abmelden', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ endpoint: sub.endpoint }),
            });
            await sub.unsubscribe();
        }
        return 'off';
    },
};

// Unterschriftsfeld: Alpine-Komponente, liefert PNG als Data-URL an Livewire
document.addEventListener('alpine:init', () => {
    window.Alpine.data('signaturePad', (model) => ({
        drawing: false,
        empty: true,
        init() {
            const c = this.$refs.canvas;
            const ratio = window.devicePixelRatio || 1;
            c.width = c.offsetWidth * ratio;
            c.height = c.offsetHeight * ratio;
            const ctx = c.getContext('2d');
            ctx.scale(ratio, ratio);
            ctx.lineWidth = 2.2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#003d8f';
            this.ctx = ctx;
        },
        pos(e) {
            const r = this.$refs.canvas.getBoundingClientRect();
            const p = e.touches ? e.touches[0] : e;
            return { x: p.clientX - r.left, y: p.clientY - r.top };
        },
        start(e) {
            this.drawing = true;
            const { x, y } = this.pos(e);
            this.ctx.beginPath();
            this.ctx.moveTo(x, y);
        },
        move(e) {
            if (!this.drawing) return;
            const { x, y } = this.pos(e);
            this.ctx.lineTo(x, y);
            this.ctx.stroke();
            this.empty = false;
        },
        end() {
            if (!this.drawing) return;
            this.drawing = false;
            this.$wire.set(model, this.empty ? null : this.$refs.canvas.toDataURL('image/png'));
        },
        clear() {
            const c = this.$refs.canvas;
            this.ctx.clearRect(0, 0, c.width, c.height);
            this.empty = true;
            this.$wire.set(model, null);
        },
    }));
});
