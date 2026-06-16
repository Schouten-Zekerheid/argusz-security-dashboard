import Chart from "chart.js/auto";

window.Chart = Chart;
window.Argusz = window.Argusz || {};

window.Argusz.createChartCheckboxIcon = function(color, checked) {
    const size = 16;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    ctx.lineWidth = 2;
    const r = 3;
    if (ctx.roundRect) {
        ctx.beginPath();
        ctx.roundRect(1.5, 1.5, size - 3, size - 3, r);
        if (checked) {
            ctx.fillStyle = color;
            ctx.fill();
        } else {
            ctx.strokeStyle = color;
            ctx.stroke();
        }
    } else {
        if (checked) {
            ctx.fillStyle = color;
            ctx.fillRect(1.5, 1.5, size - 3, size - 3);
        } else {
            ctx.strokeStyle = color;
            ctx.strokeRect(1.5, 1.5, size - 3, size - 3);
        }
    }
    if (checked) {
        ctx.strokeStyle = '#0f172a';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.beginPath();
        ctx.moveTo(4.5, 8);
        ctx.lineTo(7, 10.5);
        ctx.lineTo(11.5, 5);
        ctx.stroke();
    }
    return canvas;
};
