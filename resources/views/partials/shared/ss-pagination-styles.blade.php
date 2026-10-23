<style>
.ss-pagination {
  --ss-pagination-red: #8CFA01;
  --ss-pagination-red-dim: rgba(140, 250, 1, 0.16);
  --ss-pagination-border: rgba(0, 0, 0, 0.07);
  --ss-pagination-card: #FFFFFF;
  --ss-pagination-muted: #8A8F98;
  --ss-pagination-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  width: 100%;
}
.ss-pagination-summary {
  font-size: 12.5px;
  color: var(--ss-pagination-muted);
  margin: 0;
  text-align: left;
  flex-shrink: 0;
}
.ss-pagination-controls {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 6px;
  flex-wrap: wrap;
  margin-left: auto;
}
.ss-page-btn {
  min-width: 36px;
  height: 36px;
  padding: 0 10px;
  border-radius: 8px;
  border: 1px solid var(--ss-pagination-border);
  background: var(--ss-pagination-card);
  color: var(--ss-pagination-muted);
  font-size: 13px;
  font-weight: 600;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  cursor: pointer;
  transition: border-color 0.15s, color 0.15s, background 0.15s;
  box-shadow: var(--ss-pagination-shadow);
}
a.ss-page-btn:hover {
  border-color: var(--ss-pagination-red);
  color: #3d6b00;
  background: var(--ss-pagination-red-dim);
}
.ss-page-btn.active {
  background: var(--ss-pagination-red);
  border-color: var(--ss-pagination-red);
  color: #142000;
  box-shadow: 0 2px 8px rgba(140, 250, 1, 0.25);
}
.ss-page-btn.disabled {
  opacity: 0.4;
  cursor: not-allowed;
  box-shadow: none;
}
.pagination-wrap { margin-top: 16px; }
</style>
