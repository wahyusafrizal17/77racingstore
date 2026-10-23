<style>
.chat-premium {
  display: flex;
  flex-direction: column;
  padding: 12px 12px 18px;
  background: #f4f6f8 !important;
  gap: 0 !important;
}
.chat-premium .chat-item { display: contents; }
.chat-premium .chat-day {
  display: block;
  align-self: center;
  margin: 14px 0 10px;
  font-size: 11px;
  font-weight: 500;
  color: #94a3b8;
  letter-spacing: .02em;
}
.chat-premium .chat-row {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-top: 14px;
}
.chat-premium .chat-row.is-continue { margin-top: 4px; }
.chat-premium .chat-row.is-mine { flex-direction: row-reverse; justify-content: flex-start; }
.chat-premium .chat-avatar-wrap {
  position: relative;
  width: 32px;
  height: 32px;
  flex-shrink: 0;
}
.chat-premium .chat-avatar {
  width: 32px;
  height: 32px;
  min-width: 32px;
  min-height: 32px;
  border-radius: 50%;
  overflow: hidden;
  background: #dbe3ea;
  display: grid;
  place-items: center;
  color: #64748b;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .02em;
}
.chat-premium .chat-avatar img {
  width: 32px !important;
  height: 32px !important;
  max-width: none !important;
  max-height: none !important;
  object-fit: cover;
  object-position: center;
  display: block;
  border-radius: 50%;
}
.chat-premium .chat-avatar.is-initials { background: #3b82f6; color: #fff; }
.chat-premium .chat-row:not(.is-mine) .chat-avatar.is-initials { background: #cbd5e1; color: #475569; }
.chat-premium .chat-row.is-continue .chat-avatar-wrap,
.chat-premium .chat-row.is-continue .chat-avatar { visibility: hidden; }
.chat-premium .chat-online {
  position: absolute;
  right: -1px;
  bottom: -1px;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #22c55e;
  border: 2px solid #f4f6f8;
  box-sizing: border-box;
  z-index: 1;
  pointer-events: none;
}
.chat-premium .chat-col {
  max-width: calc(100% - 48px);
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.chat-premium .chat-row.is-mine .chat-col { align-items: flex-end; }
.chat-premium .chat-name {
  margin: 0 6px 4px;
  font-size: 11px;
  font-weight: 500;
  color: #94a3b8;
}
.chat-premium .chat-row.is-continue .chat-name,
.chat-premium .chat-row.is-mine .chat-name { display: none; }
.chat-premium .chat-inner {
  max-width: 100%;
  padding: 10px 12px 7px;
  border-radius: 6px 16px 16px 16px;
  background: #e8edf3;
  border: 0;
  color: #1e2937;
  box-shadow: none;
}
.chat-premium .chat-row.is-mine .chat-inner {
  background: #fff;
  color: #1e2937;
  border: 1px solid #eef0f3;
  box-shadow: 0 2px 8px rgba(15, 23, 42, .05);
  border-radius: 16px 6px 16px 16px;
}
.chat-premium .chat-row.is-continue:not(.is-mine) .chat-inner { border-radius: 16px; }
.chat-premium .chat-row.is-continue.is-mine .chat-inner { border-radius: 16px; }
.chat-premium .chat-text {
  margin: 0;
  font-size: 13px;
  line-height: 1.45;
  color: #1e2937;
  white-space: pre-wrap;
  word-break: break-word;
}
.chat-premium .chat-time {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 4px;
  margin-top: 5px;
  font-size: 10px;
  color: #94a3b8;
  opacity: 1;
}
.chat-premium .chat-ticks {
  display: inline-flex;
  width: 14px;
  height: 10px;
  color: #94a3b8;
}
.chat-premium .r-chat-bubble-image,
.chat-premium .dm-chat-bubble-image {
  display: block;
  max-width: 220px;
  max-height: 180px;
  border-radius: 10px;
  margin-bottom: 6px;
  object-fit: cover;
}
.chat-premium .r-chat-bubble-file,
.chat-premium .dm-chat-bubble-file {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
  padding: 8px 10px;
  border-radius: 10px;
  background: rgba(15, 23, 42, 0.06);
  color: inherit;
  text-decoration: none;
  font-size: 12px;
}
.chat-premium .r-chat-bubble-file-name,
.chat-premium .dm-chat-bubble-file-name {
  display: block;
  font-weight: 700;
  max-width: 160px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.chat-premium .r-chat-bubble-file-size,
.chat-premium .dm-chat-bubble-file-size { display: block; opacity: .75; font-size: 11px; }
.chat-premium .r-chat-order-card,
.chat-premium .dm-chat-order-card {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 6px;
  padding: 8px;
  border-radius: 12px;
  text-decoration: none;
  color: inherit;
  background: rgba(15, 23, 42, 0.05);
  border: 1px solid rgba(15, 23, 42, 0.08);
  max-width: 260px;
}
.chat-premium .r-chat-order-card:hover,
.chat-premium .dm-chat-order-card:hover { border-color: #fca5a5; }
.chat-premium .r-chat-order-img,
.chat-premium .dm-chat-order-img {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  object-fit: cover;
  background: #fff;
  flex-shrink: 0;
  border: 1px solid rgba(15, 23, 42, .08);
}
.chat-premium .r-chat-order-img.is-empty,
.chat-premium .dm-chat-order-img.is-empty {
  display: grid;
  place-items: center;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 700;
}
.chat-premium .r-chat-order-body,
.chat-premium .dm-chat-order-body { min-width: 0; flex: 1; }
.chat-premium .r-chat-order-ref,
.chat-premium .dm-chat-order-ref { display: block; font-size: 11px; font-weight: 700; opacity: .85; }
.chat-premium .r-chat-order-product,
.chat-premium .dm-chat-order-product {
  display: block;
  margin-top: 2px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.chat-premium .r-chat-order-meta,
.chat-premium .dm-chat-order-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: 4px;
}
.chat-premium .r-chat-order-status,
.chat-premium .dm-chat-order-status { font-size: 10px; opacity: .8; }
.chat-premium .r-chat-order-total,
.chat-premium .dm-chat-order-total { font-size: 11px; font-weight: 700; }
.chat-premium .chat-row.is-mine .r-chat-bubble-file,
.chat-premium .chat-row.is-mine .dm-chat-bubble-file {
  background: #f1f5f9;
  color: inherit;
}
.chat-premium .chat-row.is-mine .r-chat-order-card,
.chat-premium .chat-row.is-mine .dm-chat-order-card {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: inherit;
}
</style>
