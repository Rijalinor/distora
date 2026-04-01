<style>
    .pillar-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    .pillar-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.75rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }
    .pillar-card:hover {
        transform: translateY(-5px);
        border-color: var(--accent);
        box-shadow: 0 12px 24px -10px rgba(0,0,0,0.3);
    }
    .pillar-card::after {
        content: "->";
        position: absolute;
        right: 1.5rem;
        bottom: 1.5rem;
        font-size: 1.5rem;
        color: var(--text-muted);
        opacity: 0;
        transition: all 0.3s;
    }
    .pillar-card:hover::after {
        opacity: 1;
        right: 1.25rem;
        color: var(--accent);
    }
    .pillar-icon {
        font-size: 2.2rem;
        margin-bottom: 1rem;
    }
    .pillar-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }
    .pillar-desc {
        font-size: 0.875rem;
        color: var(--text-muted);
        line-height: 1.5;
        margin-bottom: 1.5rem;
    }
    .pillar-stat {
        margin-top: auto;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--accent);
        background: rgba(99, 102, 241, 0.1);
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        align-self: flex-start;
    }
</style>