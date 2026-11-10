# REST API / future ChatGPT connection

Base URL: https://samiprimeresearch.in/wp-json/sami-bridge/v1

The bridge is **disabled by default**. All endpoints require an authenticated WordPress user with the dedicated \`spsb_propose\` capability or a logged-in administrator, and the bridge must be enabled.

Use WordPress **Application Passwords** only over HTTPS. Store credentials in a supported connector secret store, not in a chat, GitHub, or plaintext script. Never use the main WordPress administrator's Application Password for the bot.

| Method | Endpoint | Effect |
|---|---|---|
| GET | \`/health\` | Current bridge status, version; no changes |
| GET | \`/pages?search=grievance\` | Published page metadata; no changes |
| GET | \`/pages/372/widgets\` | Supported widget IDs, hashes, previews; no changes |
| GET | \`/proposals\` | Proposal IDs and statuses |
| POST | \`/proposals\` | Submit sanitized proposal for admin review, **never publishes** |

Example POST to /proposals:

\`\`\`json
{
  "page_id": 372,
  "widget_id": "8f5c19a",
  "expected_hash": "REPLACE_WITH_HASH_RETURNED_BY_WIDGET_LOOKUP",
  "content": "<table><thead><tr><th>Month</th><th>Received</th></tr></thead><tbody><tr><td>October 2026</td><td>0</td></tr></tbody></table>"
}
\`\`\`

The widget ID and expected_hash shown above are illustrative. Retrieve the real values first. Proposing never changes the public page. A logged-in WordPress administrator reviews and explicitly approves (or rejects) from **Tools → SAMI Site Bridge**. Approval compares the live content hash, stores original Elementor JSON, and changes only the specified supported widget. Conditional rollback refuses to overwrite changes made after approval.

### Separate connector required

A GitHub repository and plugin installation do **not** grant ChatGPT automatic WordPress access. Build a supported secure connector or custom MCP service against the OpenAPI contract in [openapi.yaml](openapi.yaml), with secrets stored outside the chat.

### Security

- Do not share Application Passwords, basic authentication headers, or admin credentials in screenshots.
- Use a dedicated limited role; do not grant the bot WordPress administrator rights.
- In case of compromise: switch the bridge OFF in Tools and revoke the Application Password.
- Test on staging, compare the rendered Elementor page, and verify all grievance counts against records.
