<style>
@keyframes ssDashFadeUp {
  from { opacity: 0; transform: translateY(18px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes ssDashShimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

@keyframes ssDashSpin {
  to { transform: rotate(360deg); }
}

.ss-dash-page { position: relative; }

.ss-dash-enter {
  animation: ssDashFadeUp 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
  animation-delay: var(--ss-delay, 0ms);
}

.ss-dash-enter-child {
  animation: ssDashFadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
  animation-delay: calc(var(--ss-delay, 0ms) + var(--ss-stagger, 0) * 70ms);
}

.ss-dash-loading {
  position: fixed;
  inset: 0;
  z-index: 40;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(244, 245, 247, 0.72);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

.ss-dash-loading-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  padding: 22px 28px;
  border-radius: 14px;
  background: #fff;
  border: 1px solid rgba(0,0,0,0.06);
  box-shadow: 0 8px 32px rgba(0,0,0,0.08);
}

.ss-dash-spinner {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 3px solid rgba(140, 250, 1, 0.15);
  border-top-color: #3d6b00;
  animation: ssDashSpin 0.75s linear infinite;
}

.ss-dash-loading-text {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
}

.ss-dash-loading-sub {
  font-size: 11.5px;
  color: #8A8F98;
  margin-top: -6px;
}

.ss-dashboard.is-loading .ss-dash-content {
  opacity: 0.45;
  pointer-events: none;
  filter: blur(1px);
  transition: opacity 0.25s ease, filter 0.25s ease;
}

.ss-dashboard .branch-bar {
  width: 0 !important;
  transition: width 1s cubic-bezier(0.22, 1, 0.36, 1);
}

.ss-dashboard.is-ready .branch-bar {
  width: var(--bar-width) !important;
}

@media (prefers-reduced-motion: reduce) {
  .ss-dash-enter,
  .ss-dash-enter-child,
  .ss-dashboard .branch-bar {
    animation: none !important;
    transition: none !important;
    opacity: 1 !important;
    transform: none !important;
    width: var(--bar-width) !important;
  }
}

/* html2canvas restarts enter animations on clone — force final visible state */
.ss-dashboard-capture.ss-dashboard-capture-exporting .ss-dash-enter,
.ss-dashboard-capture.ss-dashboard-capture-exporting .ss-dash-enter-child {
  animation: none !important;
  transition: none !important;
  opacity: 1 !important;
  transform: none !important;
  filter: none !important;
}

.ss-dashboard-capture.ss-dashboard-capture-exporting .branch-bar {
  width: var(--bar-width, 0%) !important;
  transition: none !important;
}
</style>
