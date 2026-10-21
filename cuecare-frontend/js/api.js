window.CueCareAPI = {
  request: async function (path, options = {}) {
    console.info("[CueCare demo] API request", path, options);
    return { ok: true, data: {} };
  },
  toast: function (message) {
    let toast = document.querySelector(".toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.className = "toast";
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add("show");
    clearTimeout(window.__cueToast);
    window.__cueToast = setTimeout(() => toast.classList.remove("show"), 2600);
  },
};
const CUECARE_CATEGORIES = ["Billiards", "AutoCare", "Food & Beverages"];
function cuecareAddWorkspaceContext() {
  const page = document.body.dataset.page;
  if (
    !["dashboard", "inventory", "pos"].includes(page) ||
    document.querySelector(".category-switcher")
  )
    return;
  const style = document.createElement("style");
  style.id = "cuecare-workspace-style";
  style.textContent =
    '.category-switcher{display:flex;gap:8px;margin:-10px 0 22px;overflow:auto;padding-bottom:2px}.category-tab{border:1px solid #303646;background:#191d27;color:#9ca3b5;border-radius:9px;padding:9px 13px;font-size:11px;font-weight:700;white-space:nowrap}.category-tab:hover,.category-tab.active{background:#42d5e71a;border-color:#42d5e76b;color:#42d5e7}.category-tab span{display:inline-block;width:6px;height:6px;border-radius:50%;background:#42d5e7;margin-right:6px}.category-tab:nth-child(2) span{background:#f6d34c}.category-tab:nth-child(3) span{background:#f25aaa}.inventory-ipo{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:-4px 0 18px}.inventory-ipo article{background:#151821;border:1px solid #272c38;border-radius:12px;padding:14px}.inventory-ipo small{display:block;text-transform:uppercase;letter-spacing:1px;font-size:9px;font-weight:700;margin-bottom:6px}.inventory-ipo strong{font-family:"DM Sans";font-size:11px;display:block;margin-bottom:4px}.inventory-ipo p{font-size:10px;color:#8b91a3;margin:0;line-height:1.45}.inventory-ipo article:nth-child(1) small{color:#42d5e7}.inventory-ipo article:nth-child(2) small{color:#f6d34c}.inventory-ipo article:nth-child(3) small{color:#f25aaa}.brand-image{width:35px;height:35px;object-fit:contain;background:#fff;border-radius:50%;display:block}.brand-lockup--large .brand-image{width:42px;height:42px}@media(max-width:650px){.inventory-ipo{grid-template-columns:1fr}.category-switcher{margin-top:-5px}}';
  document.head.appendChild(style);
  const link = document.querySelector('link[href*="css/style.css"]');
  document.querySelectorAll(".brand-mark").forEach((mark) => {
    const image = document.createElement("img");
    image.className = "brand-image";
    image.alt = "CueCare logo";
    image.src = new URL(
      "../assets/logo.png",
      link ? link.href : window.location.href,
    ).href;
    mark.replaceWith(image);
  });
  const heading = document.querySelector(".page-heading");
  if (!heading) return;
  const switcher = document.createElement("section");
  switcher.className = "category-switcher";
  switcher.setAttribute("aria-label", "Program category");
  switcher.innerHTML = CUECARE_CATEGORIES.map(
    (category, index) =>
      `<button class="category-tab ${index === 0 ? "active" : ""}" type="button" data-category-tab="${category}"><span></span>${category}</button>`,
  ).join("");
  heading.after(switcher);
  if (page === "inventory") {
    const ipo = document.createElement("section");
    ipo.className = "inventory-ipo";
    ipo.innerHTML =
      "<article><small>Input</small><strong>Product and stock information</strong><p>Item, category, quantity, price, location, and movement details.</p></article><article><small>Process</small><strong>Validate and update inventory</strong><p>Check availability, record the movement, and apply confirmed stock rules.</p></article><article><small>Output</small><strong>Updated inventory balance</strong><p>New quantity, availability status, and an auditable adjustment record.</p></article>";
    switcher.after(ipo);
  }
  switcher.querySelectorAll("[data-category-tab]").forEach((button) =>
    button.addEventListener("click", () => {
      switcher
        .querySelectorAll(".category-tab")
        .forEach((tab) => tab.classList.remove("active"));
      button.classList.add("active");
      const filter = document.querySelector("#categoryFilter");
      if (filter) {
        filter.value =
          button.dataset.categoryTab === "Food & Beverages"
            ? "bar"
            : button.dataset.categoryTab.toLowerCase();
        filter.dispatchEvent(new Event("change"));
      }
      window.CueCareAPI.toast(
        `${button.dataset.categoryTab} category selected`,
      );
    }),
  );
}
document.addEventListener("click", (event) => {
  const button = event.target.closest("[data-toast]");
  if (button) {
    event.preventDefault();
    window.CueCareAPI.toast(button.dataset.toast);
  }
});
document.addEventListener("DOMContentLoaded", cuecareAddWorkspaceContext);
