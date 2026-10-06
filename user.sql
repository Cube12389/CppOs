-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2026-10-06 19:41:09
-- 服务器版本： 5.7.26
-- PHP 版本： 7.4.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `user`
--

-- --------------------------------------------------------

--
-- 表的结构 `ai_config`
--

CREATE TABLE `ai_config` (
  `id` int(11) NOT NULL,
  `rate_limit` int(11) DEFAULT '3',
  `window_minutes` int(11) DEFAULT '60',
  `enabled` tinyint(4) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `ai_config`
--

INSERT INTO `ai_config` (`id`, `rate_limit`, `window_minutes`, `enabled`) VALUES
(1, 3, 60, 1);

-- --------------------------------------------------------

--
-- 表的结构 `ai_server`
--

CREATE TABLE `ai_server` (
  `uid` int(100) NOT NULL,
  `ip` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `num` int(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- 转存表中的数据 `ai_server`
--

INSERT INTO `ai_server` (`uid`, `ip`, `num`) VALUES
(1, '127.0.0.1', 39),
(2, '192.168.21.238', 7),
(3, '192.168.21.205', 13),
(4, '192.168.14.49', 0);

-- --------------------------------------------------------

--
-- 表的结构 `ai_usage`
--

CREATE TABLE `ai_usage` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `used_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `ai_usage`
--

INSERT INTO `ai_usage` (`id`, `uid`, `used_at`) VALUES
(1, 1, '2026-10-04 17:07:47'),
(2, 1, '2026-10-04 18:16:45'),
(3, 1, '2026-10-04 18:21:16'),
(4, 1, '2026-10-04 18:22:54'),
(5, 1, '2026-10-04 18:25:01'),
(6, 1, '2026-10-04 18:25:20'),
(8, 1, '2026-10-04 18:53:51'),
(9, 1, '2026-10-04 19:05:33'),
(11, 1, '2026-10-04 19:08:19'),
(13, 1, '2026-10-04 19:10:21'),
(15, 1, '2026-10-04 19:56:58'),
(16, 1, '2026-10-04 19:57:33'),
(17, 1, '2026-10-04 20:04:29'),
(18, 1, '2026-10-04 20:14:15'),
(19, 1, '2026-10-04 20:21:39'),
(20, 1, '2026-10-04 20:22:38'),
(21, 1, '2026-10-04 20:23:12'),
(22, 1, '2026-10-04 20:25:13'),
(23, 1, '2026-10-04 20:31:29'),
(24, 1, '2026-10-04 20:31:48'),
(25, 1, '2026-10-04 20:33:18'),
(26, 1, '2026-10-04 20:36:45'),
(27, 1, '2026-10-04 20:37:51'),
(28, 1, '2026-10-04 21:22:04'),
(29, 1, '2026-10-04 21:22:48'),
(30, 1, '2026-10-04 21:24:49'),
(31, 1, '2026-10-04 21:25:58'),
(33, 1, '2026-10-05 07:35:15'),
(34, 1, '2026-10-05 07:44:42'),
(35, 1, '2026-10-05 15:22:34'),
(36, 1, '2026-10-05 15:47:34'),
(37, 1, '2026-10-05 15:50:26'),
(38, 1, '2026-10-05 19:00:53'),
(39, 1, '2026-10-05 19:01:12'),
(40, 1, '2026-10-05 19:01:23'),
(41, 1, '2026-10-05 19:03:32'),
(42, 1, '2026-10-05 20:33:53'),
(43, 1, '2026-10-05 20:34:28'),
(44, 1, '2026-10-05 20:49:55'),
(45, 1, '2026-10-05 21:11:58'),
(46, 1, '2026-10-05 21:13:17'),
(47, 1, '2026-10-05 21:15:36'),
(48, 1, '2026-10-05 21:15:58'),
(55, 1, '2026-10-05 21:43:33'),
(56, 1, '2026-10-05 21:47:00'),
(57, 1, '2026-10-05 21:50:36'),
(58, 1, '2026-10-05 21:50:51'),
(59, 1, '2026-10-05 21:51:26'),
(60, 1, '2026-10-06 10:48:07'),
(61, 1, '2026-10-06 10:48:37'),
(62, 1, '2026-10-06 19:14:41'),
(63, 1, '2026-10-06 19:14:57'),
(64, 1, '2026-10-06 19:15:52'),
(65, 1, '2026-10-06 19:16:26'),
(66, 1, '2026-10-06 19:16:46'),
(67, 1, '2026-10-06 19:19:13'),
(32, 2, '2026-10-04 21:39:02'),
(49, 2, '2026-10-05 21:34:47'),
(50, 2, '2026-10-05 21:34:52'),
(51, 2, '2026-10-05 21:34:58'),
(52, 2, '2026-10-05 21:35:12'),
(53, 2, '2026-10-05 21:35:25'),
(54, 2, '2026-10-05 21:35:34'),
(7, 1010, '2026-10-04 18:28:15'),
(10, 1010, '2026-10-04 19:08:16'),
(12, 1010, '2026-10-04 19:08:47'),
(14, 1010, '2026-10-04 19:10:24');

-- --------------------------------------------------------

--
-- 表的结构 `cook`
--

CREATE TABLE `cook` (
  `uid` int(100) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `UserUid` int(100) NOT NULL,
  `A` longtext COLLATE utf8_unicode_ci NOT NULL,
  `B` longtext COLLATE utf8_unicode_ci NOT NULL,
  `C` longtext COLLATE utf8_unicode_ci NOT NULL,
  `D` longtext COLLATE utf8_unicode_ci NOT NULL,
  `E` longtext COLLATE utf8_unicode_ci NOT NULL,
  `F` longtext COLLATE utf8_unicode_ci NOT NULL,
  `G` longtext COLLATE utf8_unicode_ci NOT NULL,
  `H` longtext COLLATE utf8_unicode_ci NOT NULL,
  `I` longtext COLLATE utf8_unicode_ci NOT NULL,
  `J` longtext COLLATE utf8_unicode_ci NOT NULL,
  `K` longtext COLLATE utf8_unicode_ci NOT NULL,
  `L` longtext COLLATE utf8_unicode_ci NOT NULL,
  `M` longtext COLLATE utf8_unicode_ci NOT NULL,
  `N` longtext COLLATE utf8_unicode_ci NOT NULL,
  `O` longtext COLLATE utf8_unicode_ci NOT NULL,
  `P` longtext COLLATE utf8_unicode_ci NOT NULL,
  `Q` longtext COLLATE utf8_unicode_ci NOT NULL,
  `R` longtext COLLATE utf8_unicode_ci NOT NULL,
  `S` longtext COLLATE utf8_unicode_ci NOT NULL,
  `T` longtext COLLATE utf8_unicode_ci NOT NULL,
  `U` longtext COLLATE utf8_unicode_ci NOT NULL,
  `V` longtext COLLATE utf8_unicode_ci NOT NULL,
  `W` longtext COLLATE utf8_unicode_ci NOT NULL,
  `X` longtext COLLATE utf8_unicode_ci NOT NULL,
  `Y` longtext COLLATE utf8_unicode_ci NOT NULL,
  `Z` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- 转存表中的数据 `cook`
--

INSERT INTO `cook` (`uid`, `name`, `UserUid`, `A`, `B`, `C`, `D`, `E`, `F`, `G`, `H`, `I`, `J`, `K`, `L`, `M`, `N`, `O`, `P`, `Q`, `R`, `S`, `T`, `U`, `V`, `W`, `X`, `Y`, `Z`) VALUES
(2, '贪心与证明', 1, '1 CodeForces 1882E1	Two Permutations (Easy Version)\r\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nconst int N = 2505;\r\nvector<int> ans[2];\r\nint a[N], pos[N];\r\n\r\nbool sw(int x, int n) {\r\n	vector<int> v(n + 1);\r\n	for (int i = 1; i <= n; i++) {\r\n		if (i < x) v[n - x + i + 1] = a[i];\r\n		else if (i > x) v[i - x] = a[i];\r\n		else v[n - x + 1] = a[i];\r\n	} for (int i = 1; i <= n; i++)\r\n		a[i] = v[i], pos[a[i]] = i;\r\n	bool flag = true;\r\n//	for (int i = 1; i <= n; i++)\r\n//		cout << a[i] << \' \';\r\n	for (int i = 2; i <= n; i++)\r\n		if (a[i] != a[i - 1] + 1) flag = false; \r\n//	cout << x << \" - \" << flag << endl;\r\n	return flag;\r\n} \r\n\r\nvoid solve(int cnt, int n) {\r\n	for (int i = 1; i <= n; i++)\r\n		pos[a[i]] = i;\r\n	for (int i = n - 1; i >= 1; i--) {\r\n		if (pos[i] != n and a[pos[i] + 1] == i + 1) continue;\r\n		if (a[1] != i + 1) {\r\n			ans[cnt].push_back(pos[i + 1] - 1);\r\n			if (sw(pos[i + 1] - 1, n)) return ;\r\n		} ans[cnt].push_back(pos[i]);\r\n		if (sw(pos[i], n)) return ;\r\n	} return ;\r\n}\r\n\r\nint main() {\r\n	int n, m;\r\n	cin >> n >> m;\r\n	for (int i = 1; i <= n; i++) \r\n		cin >> a[i];\r\n	solve(0, n);\r\n	for (int i = 1; i <= m; i++) \r\n		cin >> a[i];\r\n	solve(1, m);\r\n	if (int(abs((int)ans[0].size() - (int)ans[1].size())) % 2 == 0) {\r\n		if (ans[0].size() > ans[1].size()) {\r\n			cout << ans[0].size() << endl;\r\n			for (int i = 0; i < ans[1].size(); i++) \r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[1].size(); i < ans[0].size(); i++)\r\n				cout << ans[0][i] << \' \' << ((i % 2 == 0) ? 1 : m) << endl; \r\n		} else {\r\n			cout << ans[1].size() << endl;\r\n			for (int i = 0; i < ans[0].size(); i++) \r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[0].size(); i < ans[1].size(); i++)\r\n				cout << ((i % 2 == 0) ? 1 : n) << \' \' << ans[1][i] << endl; \r\n		}\r\n	} else if ((int)abs((int)ans[0].size() + n - (int)ans[1].size()) % 2 == 0) {\r\n		if (ans[1].size() <= ans[0].size()) {\r\n			cout << ans[0].size() + n << endl;\r\n			for (int i = 0; i < ans[1].size(); i++) \r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[1].size(); i < ans[0].size(); i++) \r\n				cout << ans[0][i] << \' \' << ((i % 2 == 0) ? 1 : m) << endl; \r\n			for (int i = ans[0].size(); i < ans[0].size() + n; i++) \r\n				cout << 1 << \' \' << ((i % 2 == 0) ? 1 : m) << endl; \r\n		} else if (ans[0].size() < ans[1].size() and ans[1].size() < ans[0].size() + n) {\r\n			cout << ans[0].size() + n << endl;\r\n			for (int i = 0; i < ans[0].size(); i++) \r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[0].size(); i < ans[1].size(); i++)\r\n				cout << 1 << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[1].size(); i < ans[0].size() + n; i++) \r\n				cout << 1 << \' \' << ((i % 2 == 0) ? 1 : m) << endl;\r\n		} else {\r\n			cout << ans[1].size() << endl;\r\n			for (int i = 0; i < ans[0].size(); i++) \r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[0].size(); i < ans[0].size() + n; i++)\r\n				cout << 1 << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[0].size() + n; i < ans[1].size(); i++)\r\n				cout << ((i % 2 == 0) ? 1 : n) << \' \' << ans[1][i] << endl; \r\n		}\r\n	} else if ((int)abs((int)ans[0].size() - (int)ans[1].size() - m) % 2 == 0) {\r\n		if (ans[0].size() <= ans[1].size()) {\r\n			cout << ans[1].size() + m << endl;\r\n			for (int i = 0; i < ans[0].size(); i++)\r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[0].size(); i < ans[1].size(); i++)\r\n				cout << ((i % 2 == 0) ? 1 : n) << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[1].size(); i < ans[1].size() + m; i++)\r\n				cout << ((i % 2 == 0) ? 1 : n) << \' \' << 1 << endl;\r\n		} else if (ans[1].size() < ans[0].size() and ans[0].size() <= ans[1].size() + m) {\r\n			cout << ans[1].size() + m << endl;\r\n			for (int i = 0; i < ans[1].size(); i++)\r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[1].size(); i < ans[0].size(); i++)\r\n				cout << ans[0][i] << \' \' << 1 << endl;\r\n			for (int i = ans[0].size(); i < ans[1].size() + m; i++)\r\n				cout << ((i % 2 == 0) ? 1 : n) << \' \' << 1 << endl;\r\n		} else {\r\n			cout << ans[0].size() << endl;\r\n			for (int i = 0; i < ans[1].size(); i++)\r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[1].size(); i < ans[1].size() + m; i++)\r\n				cout << ans[0][i] << \' \' << 1 << endl;\r\n			for (int i = ans[1].size() + m; i < ans[0].size(); i++)\r\n				cout << ans[0][i] << \' \' << ((i % 2 == 0) ? 1 : m) << endl;\r\n		}\r\n	} else cout << -1 << endl;\r\n	return 0;\r\n}', '1 Tower\r\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nstruct Node { int w, s, v; };\r\nconst int MAXN = 2e4;\r\nconst int N = 1e3 + 10;\r\nconst int M = 2e4 + 10;\r\nlong long dp[M];\r\nNode c[N];\r\n\r\nbool cmp(Node x, Node y) {\r\n	if (x.w + x.s != y.w + y.s) return x.w + x.s < y.w + y.s;\r\n	return x.v > y.v;\r\n}\r\n\r\nint main() {\r\n	memset(dp, -1, sizeof(dp));\r\n	int n;\r\n	cin >> n;\r\n	for (int i = 1; i <= n; i++)\r\n		cin >> c[i].w >> c[i].s >> c[i].v;\r\n	sort(c + 1, c + n + 1, cmp);\r\n	dp[0] = 0;\r\n	for (int i = 1; i <= n; i++) \r\n		for (int j = MAXN; j >= 0; j--) \r\n			if (j >= c[i].w and j - c[i].w <= c[i].s)\r\n				dp[j] = max(dp[j], dp[j - c[i].w] + c[i].v);\r\n	long long ans = 0;\r\n	for (int i = 0; i <= MAXN; i++)\r\n		ans = max(ans, dp[i]);\r\n	cout << ans << endl;\r\n	return 0;\r\n}', '1 01 on Tree\r\n#<bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nconst int N = 2e5 + 10;\r\nint fa[N], c[N];\r\nint s[N][2];\r\nint f[N];\r\n\r\nint find(int x) {\r\n	if (f[x] == x) return x;\r\n	return f[x] = find(f[x]);\r\n}\r\n\r\nstruct Node {\r\n	int id, zero, one;\r\n	bool operator<(const Node& x) const {\r\n		if (zero == 0) return true;\r\n		if (x.zero == 0) return false;\r\n		return (long long)one * x.zero > (long long)x.one * zero;\r\n	}\r\n};\r\n\r\nint main() {\r\n	int n;\r\n	cin >> n;\r\n	for (int i = 2; i <= n; i++)\r\n		cin >> fa[i];\r\n	for (int i = 1; i <= n; i++) {\r\n		cin >> c[i];\r\n		s[i][c[i]]++;\r\n		f[i] = i;\r\n	}\r\n	priority_queue<Node> q;\r\n	for (int i = 2; i <= n; i++)\r\n		q.push({i, s[i][0], s[i][1]});\r\n	long long ans = 0;\r\n	while (!q.empty()) {\r\n		Node u = q.top();\r\n		q.pop();\r\n		if (f[u.id] != u.id) continue;\r\n		if (s[u.id][0] != u.zero || s[u.id][1] != u.one) continue;\r\n		int p = fa[u.id];\r\n		int root = find(p);\r\n		ans += (long long)s[u.id][0] * s[root][1];\r\n		s[root][0] += s[u.id][0];\r\n		s[root][1] += s[u.id][1];\r\n		f[u.id] = root;\r\n		if (root != 1)\r\n			q.push({root, s[root][0], s[root][1]});\r\n	}\r\n	cout << ans << endl;\r\n	return 0;\r\n}', '2 Travelling Merchant\r\n#include<bits/stdc++.h>\r\nusing namespace std;\r\nconstexpr int N=200010, INF=1e9;\r\nstruct E{int u,v,r,p,id; bool operator<(const E&o)const{return r>o.r;}}e[N];\r\nint n,m,deg[N],f[N];\r\nbool vis[N];\r\nvector<int> rg[N];\r\n\r\nint main()\r\n{\r\n    ios::sync_with_stdio(false);\r\n    cin.tie(nullptr),cout.tie(nullptr);\r\n    cin>>n>>m;\r\n    for(int i=1;i<=m;i++)\r\n        cin>>e[i].u>>e[i].v>>e[i].r>>e[i].p,e[i].id=i,deg[e[i].u]++,rg[e[i].v].push_back(i);\r\n    sort(e+1,e+m+1);\r\n    fill(f+1,f+n+1,INF);\r\n    int pos[N];\r\n    for(int i=1;i<=m;i++) pos[e[i].id]=i;\r\n    queue<int> qq;\r\n    for(int i=1;i<=n;i++) if(!deg[i]) qq.push(i);\r\n    for(int i=1;i<=m;i++)\r\n    {\r\n        while(!qq.empty())\r\n        {\r\n            int u=qq.front(); qq.pop();\r\n            for(int id:rg[u])\r\n            {\r\n                if(vis[id]) continue;\r\n                vis[id]=1,deg[e[pos[id]].u]--;\r\n                if(f[u]!=INF) f[e[pos[id]].u]=min(f[e[pos[id]].u],max(e[pos[id]].r,f[u]-e[pos[id]].p));\r\n                if(!deg[e[pos[id]].u]) qq.push(e[pos[id]].u);\r\n            }\r\n        }\r\n        if(!vis[e[i].id])\r\n        {\r\n            vis[e[i].id]=1,deg[e[i].u]--;\r\n            f[e[i].u]=min(f[e[i].u],e[i].r);\r\n            if(!deg[e[i].u]) qq.push(e[i].u);\r\n        }\r\n    }\r\n    for(int i=1;i<=n;i++)\r\n        cout<<(f[i]==INF?-1:f[i])<<\" \\n\"[i==n];\r\n    return 0;\r\n}', 'none', '2 龙门题字\r\n#include<bits/stdc++.h>\r\nusing namespace std;\r\n#define frp(x) freopen(#x \".in\",\"r\",stdin),freopen(#x \".out\",\"w\",stdout)\r\nconstexpr int N=3e5+10,INF=0x3f3f3f3f;\r\nint n,m,a[N],ans[N],mis[N],pos[N];\r\nbool fix[N],usd[N];\r\nvector<int> cov[N],bel[N];\r\nstruct node{int l,r; vector<int> v;}mo[N];\r\nint main()\r\n{\r\n    ios::sync_with_stdio(false);\r\n    cin.tie(nullptr),cout.tie(nullptr);\r\n    cin>>n>>m;\r\n    for(int i=1;i<=n;i++) cin>>a[i];\r\n    int l=0;\r\n    for(int i=0;i<m;i++)\r\n    {\r\n        cin>>mo[i].l>>mo[i].r;\r\n        int len=mo[i].r-mo[i].l+1; l+=len;\r\n        mo[i].v.resize(len);\r\n        for(int j=0;j<len;j++) cin>>mo[i].v[j];\r\n        for(int p=mo[i].l;p<=mo[i].r;p++) cov[p].push_back(i);\r\n    }\r\n    for(int i=0;i<m;i++) mis[i]=0;\r\n    for(int i=1;i<=n;i++) fix[i]=false;\r\n    queue<int> q;\r\n    int dcnt=0;\r\n    for(int p=1;p<=n;p++)\r\n    {\r\n        int bst=-INF;\r\n        if(dcnt==0) bst=a[p];\r\n        for(int id:cov[p]) if(!usd[id]&&mis[id]==0) bst=max(bst,mo[id].v[p-mo[id].l]);\r\n        ans[p]=bst;\r\n        for(int id:cov[p]) if(!usd[id]&&mo[id].v[p-mo[id].l]!=ans[p]) mis[id]++;\r\n        if(ans[p]!=a[p]) dcnt++;\r\n        for(int id:cov[p]) if(!usd[id]&&mis[id]==0&&mo[id].r==p) q.push(id);\r\n        while(!q.empty())\r\n        {\r\n            int id=q.front(); q.pop();\r\n            if(usd[id]) continue;\r\n            usd[id]=true;\r\n            for(int t=mo[id].l;t<=mo[id].r;t++)\r\n                if(!fix[t])\r\n                {\r\n                    fix[t]=true;\r\n                    if(ans[t]!=a[t]) dcnt--;\r\n                    for(int jd:cov[t])\r\n                        if(!usd[jd]&&mo[jd].v[t-mo[jd].l]!=ans[t])\r\n                        {\r\n                            mis[jd]--;\r\n                            if(mis[jd]==0&&mo[jd].r<=p)\r\n                                q.push(jd);\r\n                        }\r\n                }\r\n        }\r\n    }\r\n    for(int i=1;i<=n;i++) cout<<ans[i]<<\' \';\r\n}\r\n', 'none', '1 工序安排 Job Processing\r\n#include <bits/stdc++.h>\r\nusing namespace std;\r\n\r\nconst int M = 35;\r\nconst int N = 1005;\r\nint x[M], y[M];\r\nint a[N], b[N];\r\n\r\nint main() {\r\n    int n, m1, m2;\r\n    cin >> n >> m1 >> m2;\r\n    for (int i = 1; i <= m1; i++) cin >> x[i];\r\n    for (int i = 1; i <= m2; i++) cin >> y[i];\r\n    priority_queue<pair<int,int>, vector<pair<int,int>>, greater<pair<int,int>>> pq;\r\n    for (int i = 1; i <= m1; i++) {\r\n        pq.push({x[i], i});        \r\n    }\r\n    for (int i = 1; i <= n; i++) {\r\n        auto [t, id] = pq.top(); pq.pop();\r\n        a[i] = t;                   \r\n        pq.push({t + x[id], id});  \r\n    }\r\n    sort(a + 1, a + n + 1);        \r\n    while (!pq.empty()) pq.pop();\r\n    for (int i = 1; i <= m2; i++) {\r\n        pq.push({y[i], i});\r\n    }\r\n    for (int i = 1; i <= n; i++) {\r\n        auto [t, id] = pq.top(); pq.pop();\r\n        b[i] = t;\r\n        pq.push({t + y[id], id});\r\n    }\r\n    sort(b + 1, b + n + 1, greater<int>()); \r\n    int ans1 = a[n];               \r\n    int ans2 = 0;\r\n    for (int i = 1; i <= n; i++) {\r\n        ans2 = max(ans2, a[i] + b[i]);\r\n    }\r\n    cout << ans1 << \" \" << ans2 << endl;\r\n    return 0;\r\n}', '2 DYN-Dynamite\r\n#include<bits/stdc++.h>\r\nusing namespace std;\r\n#define frp(x) freopen(#x \".in\",\"r\",stdin),freopen(#x \".out\",\"w\",stdout)\r\nconstexpr int N=3e5+10,INF=1e9;\r\nint m,n,a[N],f[N],r[N],cnt;\r\nvector<int> g[N];\r\nvoid dfs(int u,int fa,int len,bool &ok)\r\n{\r\n    f[u]=-INF,r[u]=INF;\r\n    if(a[u]) f[u]=0;\r\n    for(int v:g[u])\r\n    {\r\n        if(v==fa) continue;\r\n        dfs(v,u,len,ok);\r\n        if(!ok) return ;\r\n        f[u]=max(f[u],f[v]+1);\r\n        r[u]=min(r[u],r[v]+1);\r\n        \r\n    }\r\n    if(f[u]+r[u]<=len) f[u]=-INF;\r\n    if(f[u]==len) r[u]=0,f[u]=-INF,cnt++;\r\n    if(f[u]>len){ok=false; return ;}\r\n}\r\nbool check(int len)\r\n{\r\n    cnt=0;\r\n    bool ok=true;\r\n    dfs(1,0,len,ok);\r\n    if(!ok) return false;\r\n    if(f[1]>=0) cnt++;\r\n    return cnt<=m;\r\n}\r\nint main()\r\n{\r\n    ios::sync_with_stdio(false);\r\n    cin.tie(nullptr),cout.tie(nullptr);\r\n    cin>>n>>m;\r\n    for(int i=1;i<=n;i++) cin>>a[i];\r\n    for(int i=1,u,v;i<n;i++) cin>>u>>v,g[u].emplace_back(v),g[v].emplace_back(u);\r\n    int l=0,r=300010,ans;\r\n    while(l<=r)\r\n    {\r\n        int mid=(l+r)>>1;\r\n        if(check(mid)) ans=mid,r=mid-1;\r\n        else l=mid+1;\r\n    }\r\n    cout<<ans;\r\n}\r\n', 'none', '2 嘉年华奖券\r\n#include<bits/stdc++.h>\r\nusing namespace std;\r\ntypedef long long ll;\r\n#define frp(x) freopen(#x \".in\",\"r\",stdin),freopen(#x \".out\",\"w\",stdout)\r\nint n,m,k,a[1510][1510];\r\nvector<int> pos;\r\npriority_queue<pair<ll,int>> q;\r\nll ans;\r\nint main()\r\n{\r\n    ios::sync_with_stdio(false);\r\n    cin.tie(nullptr),cout.tie(nullptr);\r\n    cin>>n>>m>>k;\r\n    pos.resize(n);\r\n    for(int i=0;i<n;i++) for(int j=0;j<m;j++) cin>>a[i][j];\r\n    auto gain=[&](int i)->ll{return (ll)a[i][m-1-pos[i]]+a[i][k-pos[i]-1];};\r\n    for(int i=0;i<n;i++) q.push({gain(i),i});\r\n    for(int t=0;t<n*k/2;t++)\r\n    {\r\n        auto [g,i]=q.top(); \r\n        q.pop(),pos[i]++;\r\n        if(pos[i]<k) q.push({gain(i),i});\r\n    }\r\n    for(int i=0;i<n;i++)\r\n    {\r\n        for(int j=m-pos[i];j<m;j++) ans+=a[i][j];\r\n        for(int j=0;j<k-pos[i];j++) ans-=a[i][j];\r\n    }\r\n    cout<<ans;\r\n}\r\n', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none'),
(1, '构造与交互', 1, '1 CodeForces 1882E1 Two Permutations (Easy Version)\r\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nconst int N = 2505;\r\nvector<int> ans[2];\r\nint a[N], pos[N];\r\n\r\nbool sw(int x, int n) {\r\n	vector<int> v(n + 1);\r\n	for (int i = 1; i <= n; i++) {\r\n		if (i < x) v[n - x + i + 1] = a[i];\r\n		else if (i > x) v[i - x] = a[i];\r\n		else v[n - x + 1] = a[i];\r\n	} for (int i = 1; i <= n; i++)\r\n		a[i] = v[i], pos[a[i]] = i;\r\n	bool flag = true;\r\n//	for (int i = 1; i <= n; i++)\r\n//		cout << a[i] << \' \';\r\n	for (int i = 2; i <= n; i++)\r\n		if (a[i] != a[i - 1] + 1) flag = false; \r\n//	cout << x << \" - \" << flag << endl;\r\n	return flag;\r\n} \r\n\r\nvoid solve(int cnt, int n) {\r\n	for (int i = 1; i <= n; i++)\r\n		pos[a[i]] = i;\r\n	for (int i = n - 1; i >= 1; i--) {\r\n		if (pos[i] != n and a[pos[i] + 1] == i + 1) continue;\r\n		if (a[1] != i + 1) {\r\n			ans[cnt].push_back(pos[i + 1] - 1);\r\n			if (sw(pos[i + 1] - 1, n)) return ;\r\n		} ans[cnt].push_back(pos[i]);\r\n		if (sw(pos[i], n)) return ;\r\n	} return ;\r\n}\r\n\r\nint main() {\r\n	int n, m;\r\n	cin >> n >> m;\r\n	for (int i = 1; i <= n; i++) \r\n		cin >> a[i];\r\n	solve(0, n);\r\n	for (int i = 1; i <= m; i++) \r\n		cin >> a[i];\r\n	solve(1, m);\r\n	if (int(abs((int)ans[0].size() - (int)ans[1].size())) % 2 == 0) {\r\n		if (ans[0].size() > ans[1].size()) {\r\n			cout << ans[0].size() << endl;\r\n			for (int i = 0; i < ans[1].size(); i++) \r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[1].size(); i < ans[0].size(); i++)\r\n				cout << ans[0][i] << \' \' << ((i % 2 == 0) ? 1 : m) << endl; \r\n		} else {\r\n			cout << ans[1].size() << endl;\r\n			for (int i = 0; i < ans[0].size(); i++) \r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[0].size(); i < ans[1].size(); i++)\r\n				cout << ((i % 2 == 0) ? 1 : n) << \' \' << ans[1][i] << endl; \r\n		}\r\n	} else if ((int)abs((int)ans[0].size() + n - (int)ans[1].size()) % 2 == 0) {\r\n		if (ans[1].size() <= ans[0].size()) {\r\n			cout << ans[0].size() + n << endl;\r\n			for (int i = 0; i < ans[1].size(); i++) \r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[1].size(); i < ans[0].size(); i++) \r\n				cout << ans[0][i] << \' \' << ((i % 2 == 0) ? 1 : m) << endl; \r\n			for (int i = ans[0].size(); i < ans[0].size() + n; i++) \r\n				cout << 1 << \' \' << ((i % 2 == 0) ? 1 : m) << endl; \r\n		} else if (ans[0].size() < ans[1].size() and ans[1].size() < ans[0].size() + n) {\r\n			cout << ans[0].size() + n << endl;\r\n			for (int i = 0; i < ans[0].size(); i++) \r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[0].size(); i < ans[1].size(); i++)\r\n				cout << 1 << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[1].size(); i < ans[0].size() + n; i++) \r\n				cout << 1 << \' \' << ((i % 2 == 0) ? 1 : m) << endl;\r\n		} else {\r\n			cout << ans[1].size() << endl;\r\n			for (int i = 0; i < ans[0].size(); i++) \r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[0].size(); i < ans[0].size() + n; i++)\r\n				cout << 1 << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[0].size() + n; i < ans[1].size(); i++)\r\n				cout << ((i % 2 == 0) ? 1 : n) << \' \' << ans[1][i] << endl; \r\n		}\r\n	} else if ((int)abs((int)ans[0].size() - (int)ans[1].size() - m) % 2 == 0) {\r\n		if (ans[0].size() <= ans[1].size()) {\r\n			cout << ans[1].size() + m << endl;\r\n			for (int i = 0; i < ans[0].size(); i++)\r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[0].size(); i < ans[1].size(); i++)\r\n				cout << ((i % 2 == 0) ? 1 : n) << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[1].size(); i < ans[1].size() + m; i++)\r\n				cout << ((i % 2 == 0) ? 1 : n) << \' \' << 1 << endl;\r\n		} else if (ans[1].size() < ans[0].size() and ans[0].size() <= ans[1].size() + m) {\r\n			cout << ans[1].size() + m << endl;\r\n			for (int i = 0; i < ans[1].size(); i++)\r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[1].size(); i < ans[0].size(); i++)\r\n				cout << ans[0][i] << \' \' << 1 << endl;\r\n			for (int i = ans[0].size(); i < ans[1].size() + m; i++)\r\n				cout << ((i % 2 == 0) ? 1 : n) << \' \' << 1 << endl;\r\n		} else {\r\n			cout << ans[0].size() << endl;\r\n			for (int i = 0; i < ans[1].size(); i++)\r\n				cout << ans[0][i] << \' \' << ans[1][i] << endl;\r\n			for (int i = ans[1].size(); i < ans[1].size() + m; i++)\r\n				cout << ans[0][i] << \' \' << 1 << endl;\r\n			for (int i = ans[1].size() + m; i < ans[0].size(); i++)\r\n				cout << ans[0][i] << \' \' << ((i % 2 == 0) ? 1 : m) << endl;\r\n		}\r\n	} else cout << -1 << endl;\r\n	return 0;\r\n}', '1 洛谷 P16325	XOR and Swap\r\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nconst int N = 20;\r\nconst int MAXN = 1 << N;\r\nvector<pair<int, long long>> ans[2];\r\nint a[MAXN], pos[MAXN];\r\n\r\nvoid solve(int n, int xi) {\r\n	for (int i = 0; i < (1 << n); i++) \r\n		pos[a[i]] = i;\r\n	priority_queue<pair<int, int>, vector<pair<int, int>>, greater<pair<int, int>>> q;\r\n	for (int i = 0; i < (1 << n); i++)\r\n		q.emplace(a[i] ^ i, i);\r\n	while (!q.empty()) {\r\n		int u = q.top().second;\r\n		int v = a[u] ^ u; \r\n		q.pop();\r\n		if (a[u] == u) continue;\r\n		if (v <= (pos[u] ^ u)) {\r\n			int j = pos[u];\r\n			ans[xi].emplace_back(u, j);\r\n			swap(a[u], a[j]);\r\n			pos[a[u]] = u;\r\n			pos[a[j]] = j;\r\n			q.emplace(a[u] ^ u, u);\r\n			q.emplace(a[j] ^ j, j);\r\n		} \r\n	}\r\n}\r\n\r\nint main() {\r\n	int n;\r\n	cin >> n;\r\n	for (int i = 0; i < (1 << n); i++)\r\n		cin >> a[i];\r\n	solve(n, 0);\r\n	for (int i = 0; i < (1 << n); i++)\r\n		cin >> a[i];\r\n	solve(n, 1);\r\n	cout << ans[0].size() + ans[1].size() << endl;\r\n	for (int i = 0; i < ans[0].size(); i++)\r\n		cout << ans[0][i].first << \' \' << ans[0][i].second << endl;\r\n	for (int i = ans[1].size() - 1; i >= 0; i--)\r\n		cout << ans[1][i].first << \' \' << ans[1][i].second << endl;\r\n	return 0;\r\n}', '1 CodeForces 1720E	Misha and Paintings\r\n#include \r\n#include <bits/stdc++.h>\r\n\r\nusing namespace std; \r\n\r\nconst int N = 505;\r\nint c[N][N], d[N][N];\r\n\r\nint main() {\r\n	int n, k;\r\n	cin >> n >> k;\r\n	for (int i = 1; i <= n; i++)\r\n		for (int j = 1; j <= n; j++)\r\n			cin >> c[i][j];\r\n	unordered_map<int, pair<pair<int, int>, pair<int, int>>> m;\r\n	for (int i = 1; i <= n; i++) {\r\n		for (int j = 1; j <= n; j++) {\r\n			if (m.count(c[i][j])) {\r\n				m[c[i][j]].first.first = min(m[c[i][j]].first.first, i);\r\n				m[c[i][j]].first.second = min(m[c[i][j]].first.second, j);\r\n				m[c[i][j]].second.first = max(m[c[i][j]].second.first, i);\r\n				m[c[i][j]].second.second = max(m[c[i][j]].second.second, j);\r\n			} else m[c[i][j]].first = m[c[i][j]].second = make_pair(i, j);\r\n		}\r\n	} if (m.size() <= k) {\r\n		cout << k - m.size() << endl;\r\n		return 0;\r\n	} for (int l = 1; l <= n; l++) {\r\n		memset(d, 0, sizeof(d));\r\n		for (auto it = m.begin(); it != m.end(); it++) {\r\n			int i1 = max(1, it->second.second.first - l + 1);\r\n			int i2 = min(n - l + 1, it->second.first.first);\r\n			int j1 = max(1, it->second.second.second - l + 1);\r\n			int j2 = min(n - l + 1, it->second.first.second);\r\n			if (i1 > i2 or j1 > j2) continue;\r\n			d[i1][j1]++, d[i1][j2 + 1]--, d[i2 + 1][j1]--, d[i2 + 1][j2 + 1]++;\r\n		}\r\n		for (int i = 1; i <= n; i++) {\r\n			for (int j = 1; j <= n; j++) {\r\n				d[i][j] += d[i - 1][j] + d[i][j - 1] - d[i - 1][j - 1];\r\n				int tmp = d[i][j];\r\n				if ((int)m.size() - tmp == k or (int)m.size() - tmp == k - 1) {\r\n					cout << 1 << endl;\r\n					return 0;\r\n				}\r\n			}\r\n		}\r\n	} cout << 2 << endl;\r\n	return 0;\r\n}', '1 CodeForces 1270G	Subset with Zero Sum\r\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nconst int N = 1e6 + 10;\r\n\r\nbool dfs(deque<int>& ans, vector<int>& a, vector<int>& vis, int u) {\r\n	vis[u] = 1;\r\n	ans.push_back(u);\r\n	int v = u - a[u];\r\n	if (vis[v] == 2) return false;\r\n	if (vis[v] == 1) {\r\n		while (ans.front() != v)\r\n			ans.pop_front();\r\n		return true;\r\n	} if (dfs(ans, a, vis, v)) \r\n		return true;\r\n	vis[u] = 2;\r\n	ans.pop_back();\r\n	return false; \r\n}\r\n\r\nint main() {\r\n	ios::sync_with_stdio(0);\r\n	cin.tie(0);\r\n	cout.tie(0);\r\n	int T;\r\n	cin >> T;\r\n	while (T--) {\r\n		int n;\r\n		cin >> n;\r\n		vector<int> a(n + 1), vis(n + 1, 0);\r\n		vector<vector<int>> g(n + 1);\r\n		bool flag = false;\r\n		for (int i = 1; i <= n; i++)\r\n			cin >> a[i];\r\n		for (int i = 1; i <= n; i++) {\r\n			if (a[i] == 0) {\r\n				cout << 1 << endl << i << endl;\r\n				flag = true;\r\n				break;\r\n			}\r\n		} if (flag) continue;\r\n		deque<int> ans;\r\n		for (int i = 1; i <= n; i++) {\r\n			if (vis[i] == 0 and dfs(ans, a, vis, i)) {\r\n				cout << ans.size() << endl;\r\n				while (!ans.empty()) {\r\n					cout << ans.front() << \' \';\r\n					ans.pop_front();\r\n				} break;\r\n			}\r\n		} cout << endl;\r\n	} return 0;\r\n} ', 'none', 'none', '1 QOJ 9432	Permutation\r\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nvector<int> g[1005];\r\nmt19937 rd(time(0));\r\n\r\nvoid dfs(int l, int r, int& n, vector<int>& s, vector<int>& ans) {\r\n    if (l == r) {\r\n        ans.push_back(s[0]);\r\n        return ;\r\n    }\r\n    int mid = (l + r) / 2;\r\n    for (int i : s) {\r\n        g[i].clear();\r\n        g[i].push_back(i);\r\n    }\r\n    for (int i = 1; i < s.size(); i++)\r\n        swap(s[i], s[rd() % s.size()]);\r\n    vector<int> L, R;\r\n    int ll = 0, rr = s.size() - 1;\r\n    while (ll < rr) {\r\n        cout << 0 << \' \';\r\n        for (int i = 1; i <= mid; i++) cout << s[ll] << \' \';\r\n        for (int i = mid + 1; i <= n; i++) cout << s[rr] << \' \';\r\n        cout << endl;\r\n        cout.flush();\r\n        int op;\r\n        cin >> op;\r\n        if (op == 2) {\r\n            for (int i : g[s[ll]]) L.push_back(i);\r\n            for (int i : g[s[rr]]) R.push_back(i);\r\n            ll++, rr--;\r\n        } else if (op == 0) {\r\n            for (int i : g[s[ll]]) R.push_back(i);\r\n            for (int i : g[s[rr]]) L.push_back(i);\r\n            ll++, rr--;\r\n        } else if (rd() & 1) {\r\n            for (int i : g[s[ll]]) g[s[rr]].push_back(i);\r\n            ll++;\r\n        } else {\r\n            for (int i : g[s[rr]]) g[s[ll]].push_back(i);\r\n            rr--;\r\n        }\r\n    }\r\n    if (ll == rr) {\r\n        if (L.size() != mid - l + 1)\r\n            for (int i : g[s[ll]]) L.push_back(i);\r\n        else\r\n            for (int i : g[s[ll]]) R.push_back(i);\r\n    }\r\n    dfs(l, mid, n, L, ans);\r\n    dfs(mid + 1, r, n, R, ans);\r\n}\r\n\r\nint main() {\r\n    int n;\r\n    cin >> n;\r\n    vector<int> s;\r\n    for (int i = 1; i <= n; i++)\r\n        s.push_back(i);\r\n    vector<int> ans;\r\n    dfs(1, n, n, s, ans);\r\n    cout << 1 << \' \';\r\n    for (int i : ans)\r\n        cout << i << \' \';\r\n    cout << endl;\r\n    cout.flush();\r\n    return 0;\r\n}', '1 QOJ 15314	Menji，我们想你！\r\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nint n, xd, tot, p, mn;\r\nvector<vector<int>> son;\r\nvector<int> d, vis, siz;\r\n\r\nint dfs(int u) {\r\n    int maxn = d[u];\r\n    for (int v : son[u]) {\r\n        d[v] = d[u] + 1;\r\n        maxn = max(maxn, dfs(v));\r\n    } return maxn;\r\n}\r\n\r\nbool ask(int u, int k) {\r\n    int flag;\r\n    cout << \'?\' << \' \' << u << \' \' << k << endl;\r\n    cout.flush();\r\n    cin >> flag;\r\n    return (bool)flag;\r\n}\r\n\r\nvoid find(int u) {\r\n    siz[u] = 1;\r\n    for (int v : son[u])\r\n        if (!vis[v]) {\r\n            find(v);\r\n            siz[u] += siz[v];\r\n        }\r\n    if (d[u] <= xd && max(siz[u], tot - siz[u]) < mn) {\r\n        mn = max(siz[u], tot - siz[u]);\r\n        p = u;\r\n    }\r\n}\r\n\r\nint main() {\r\n    int T;\r\n    cin >> T;\r\n    while (T--) {\r\n        cin >> n;\r\n        vector<int> fa(n + 1);\r\n        d.assign(n + 1, 0);\r\n        son.assign(n + 1, {});\r\n        vis.assign(n + 1, 0);\r\n        siz.assign(n + 1, 0);\r\n        for (int i = 2; i <= n; i++)\r\n            cin >> fa[i];\r\n        for (int i = 2; i <= n; i++)\r\n            son[fa[i]].push_back(i);\r\n        d[1] = 1;\r\n        int l = 0, r = dfs(1) - 1;\r\n        while (l < r) {\r\n            int mid = (l + r) / 2;\r\n            if (ask(1, mid)) \r\n                r = mid;\r\n            else l = mid + 1;\r\n        } \r\n        xd = l + 1;\r\n\r\n        int root = 1;\r\n        tot = n;\r\n        while (1) {\r\n            mn = n + 1;\r\n            find(root);\r\n\r\n            if (ask(p, xd - d[p])) {\r\n                if (d[p] == xd) break;\r\n                root = p;\r\n                tot = siz[p];\r\n            } else {\r\n                vis[p] = 1;\r\n                tot -= siz[p];\r\n            }\r\n        }\r\n\r\n        cout << \'!\' << \' \' << p << endl;\r\n        cout.flush();\r\n    }\r\n    return 0;\r\n}', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none'),
(1006, 'NOIP Div.2-1 No.6', 1, '1 活动（activity）\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nconst long long mod = 998244353;\r\nconst int N = 1e5 + 10;\r\nlong long dp[N], c[N];\r\n\r\nint main() {\r\n	freopen(\"activity.in\", \"r\", stdin);\r\n	freopen(\"activity.out\", \"w\", stdout);\r\n	int n, x, y;\r\n	cin >> n >> x >> y;\r\n	dp[0] = c[0] = 1;\r\n	for (int i = 1; i <= x; i++) {\r\n		int l = max(0, i - n), r = i / 2;\r\n		if (l == 0) dp[i] = c[r] % mod;\r\n		else dp[i] = (c[r] + mod - c[l - 1]) % mod;\r\n		if (l <= i - y and i - y <= r) \r\n			dp[i] = (dp[i] + mod - dp[i - y]) % mod;\r\n		if (l > r) dp[i] = 0;	\r\n		c[i] = (c[i - 1] + dp[i]) % mod;\r\n	} cout << dp[x] << endl;\r\n	return 0;\r\n}', '1 期末测试（road）\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nstruct Node { int v; long long w; };\r\nconst long long INF = 0x3f3f3f3f3f3f3f3f;\r\nconst int N = 5e4 + 10;\r\nconst int K = 15;\r\nvector<Node> g[N];\r\nbool vis[N];\r\nlong long d[K][N];\r\nlong long c[K], dp[1 << K][K];\r\n\r\nvoid dij(int x) {\r\n	memset(vis, 0, sizeof(vis));\r\n	d[x][c[x]] = 0;\r\n	priority_queue<pair<int, int>, vector<pair<int, int>>, greater<pair<int, int>>> q;\r\n	q.emplace(0, c[x]);\r\n	while (!q.empty()) {\r\n		int u = q.top().second;\r\n		q.pop();\r\n		if (vis[u]) continue;\r\n		vis[u] = 1;\r\n		for (Node i : g[u]) {\r\n			int v = i.v;\r\n			long long w = i.w;\r\n			if (vis[v]) continue;\r\n			if (d[x][v] > d[x][u] + w) {\r\n				d[x][v] = d[x][u] + w;\r\n				q.emplace(d[x][v], v);\r\n			}\r\n		}\r\n	} return ;\r\n}\r\n\r\nint main() {\r\n	freopen(\"road.in\", \"r\", stdin);\r\n	freopen(\"road.out\", \"w\", stdout);\r\n	ios::sync_with_stdio(0);\r\n	cin.tie(0);\r\n	cout.tie(0);\r\n	memset(dp, 0x3f, sizeof(dp));\r\n	memset(d, 0x3f, sizeof(d));\r\n	int n, m, k, s, t;\r\n	cin >> n >> m >> k >> s >> t;\r\n	for (int i = 1; i <= m; i++) {\r\n		int u, v;\r\n		long long w;\r\n		cin >> u >> v >> w;\r\n		g[u].push_back({v, w});\r\n	} for (int i = 1; i <= k; i++)\r\n		cin >> c[i];\r\n	if (s == t) {\r\n		int st = -1;\r\n		for (int i = 1; i <= k and st == -1; i++)\r\n			if (s == c[i]) st = i;\r\n		if (st == -1) {\r\n			c[0] = c[k + 1] = s;\r\n			d[0][s] = d[k + 1][s] = 0;\r\n			k += 2;\r\n		} else {\r\n			c[0] = c[st];\r\n			swap(c[k], c[st]);\r\n			d[0][s] = d[k][s] = 0;\r\n			k++;\r\n		}\r\n	} else {\r\n		int st = -1, ed = -1;\r\n		for (int i = 1; i <= k and st == -1; i++) \r\n			if (s == c[i]) st = i;\r\n		for (int i = 1; i <= k and ed == -1; i++)\r\n			if (t == c[i]) ed = i;\r\n		if (st == -1) c[0] = s, k++;\r\n		else {\r\n			swap(c[0], c[st]);\r\n			for (int i = st + 1; i <= k; i++)\r\n				c[i - 1] = c[i];\r\n		} if (ed == -1) c[k] = t, k++;\r\n		else swap(c[ed], c[k - 1]);\r\n	} for (int i = 0; i < k; i++) \r\n		dij(i);\r\n	dp[1][0] = 0;\r\n	for (int i = 2; i < (1 << k); i++) {\r\n		for (int j = 0; j < k; j++) {\r\n			if ((i & (1 << j)) == 0) continue;\r\n			int tmp = (i ^ (1 << j));\r\n			for (int x = 0; x < k; x++) {\r\n				if ((tmp & (1 << x)) == 0) continue;\r\n				dp[i][j] = min(dp[i][j], dp[tmp][x] + d[x][c[j]]);\r\n			}\r\n		}\r\n	} if (dp[(1 << k) - 1][k - 1] == INF) cout << -1 << \'\\n\';\r\n	else cout << dp[(1 << k) - 1][k - 1] << \'\\n\';\r\n	return 0;\r\n}\r\n\r\n\r\n', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none'),
(3, 'DP与DP优化', 1, '1 Permutation Oddness\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nconst int mod = 1e9 + 7;\r\nconst int N = 55;\r\nconst int of = N * N;\r\nint dp[N][N][2 * N * N];\r\n\r\nint main() {\r\n	int n, m;\r\n	cin >> n >> m;\r\n	dp[0][0][of] = 1;\r\n	int b = n * n;\r\n	for (int i = 1; i <= n; i++) \r\n		for (int j = 0; j <= i; j++) \r\n			for (int k = -b; k <= b; k++) \r\n				dp[i][j][k + of] = (dp[i][j][k + of]\r\n					+ ((j - 1 < 0 or k + 2 * i >  b) ? 0 : 1LL * dp[i - 1][j - 1][k + 2 * i + of])\r\n				    + ((j + 1 > n or k - 2 * i < -b) ? 0 : 1LL * dp[i - 1][j + 1][k - 2 * i + of]) * (j + 1) * (j + 1)\r\n					+ (2 * j + 1) *                        1LL * dp[i - 1][j][k + of]\r\n				) % mod;\r\n	cout << dp[n][0][m + of] << endl;\r\n	return 0;\r\n}', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none'),
(4, '字符串专题', 1, '1 [POI 2000 R3] 公共串\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nconst int base1 = 31;\r\nconst int base2 = 37;\r\nconst int N = 2005;\r\nstring s[10];\r\nint f[N];\r\n\r\npair<unsigned long long, unsigned long long> get(string c) {\r\n    unsigned long long h1 = 0, h2 = 0;\r\n    for (int i = 0; i < c.size(); i++) {\r\n        h1 = h1 * base1 + int(c[i] - \'a\' + 1);\r\n        h2 = h2 * base2 + int(c[i] - \'a\' + 1);\r\n    } return {h1, h2};\r\n}\r\n\r\nbool check(int x, int& n) {\r\n    map<pair<unsigned long long, unsigned long long>, int> m;\r\n    for (int i = 1; i <= n; i++) {\r\n        set<pair<unsigned long long, unsigned long long>> v;\r\n        for (int j = 0; j + x - 1 < s[i].size(); j++) {\r\n            string t = s[i].substr(j, x);\r\n            v.insert(get(t));\r\n        } for (auto j = v.begin(); j != v.end(); j++)\r\n            m[*j]++;\r\n    } for (auto i = m.begin(); i != m.end(); i++)\r\n        if (i->second == n)\r\n            return true;\r\n    return false;\r\n}\r\n\r\nint main() {\r\n    int n;\r\n    cin >> n;\r\n    for (int i = 1; i <= n; i++)\r\n        cin >> s[i];\r\n    int l = 0, r = 2000;\r\n    while (l < r) {\r\n        int mid = (l + r + 1) / 2;\r\n        if (check(mid, n)) l = mid;\r\n        else r = mid - 1;\r\n    } cout << l << endl;\r\n    return 0;\r\n}', 'none', '1 最长异或路径\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nstruct Edge { int n; Edge* nxt[2]; };\r\nstruct Node { int v; unsigned int w; };\r\nconst int N = 1e5 + 10;\r\nvector<Node> g[N];\r\nint d[N];\r\n\r\nvoid dfs(int u, int fa) {\r\n    for (Node i : g[u]) {\r\n        int v = i.v;\r\n        if (v == fa) continue;\r\n        unsigned int w = i.w;\r\n        d[v] = d[u] ^ w;\r\n        dfs(v, u);\r\n    } return ;\r\n}\r\n\r\nvoid add(int x, int st, Edge* n) {\r\n    if (st < 0) return ;\r\n    int tmp = (x >> st) & 1;\r\n    if (n->nxt[tmp] == nullptr) {\r\n        n->nxt[tmp] = new Edge;\r\n        n->nxt[tmp]->n = 0;\r\n    } n->nxt[tmp]->n++;\r\n    add(x, st - 1, n->nxt[tmp]);\r\n}\r\n\r\nint query(int x, int st, Edge* n) {\r\n    if (st < 0) return 0;\r\n    int tmp = (x >> st) & 1;\r\n    if (n->nxt[tmp ^ 1] != nullptr && n->nxt[tmp ^ 1]->n > 0) \r\n        return query(x, st - 1, n->nxt[tmp ^ 1]) + ((tmp ^ 1) << st);\r\n    return query(x, st - 1, n->nxt[tmp]) + (tmp << st);\r\n}\r\n\r\nint main() {\r\n    int n;\r\n    cin >> n;\r\n    for (int i = 1; i < n; i++) {\r\n        unsigned int w;\r\n        int u, v;\r\n        cin >> u >> v >> w;\r\n        g[u].push_back({v, w});\r\n        g[v].push_back({u, w});\r\n    } d[1] = 0;\r\n    dfs(1, 1);\r\n    Edge* head = new Edge;\r\n    head->n = 0;\r\n    for (int i = 1; i <= n; i++) \r\n        add(d[i], 30, head);\r\n    int maxn = 0;\r\n    for (int i = 1; i <= n; i++) \r\n        maxn = max(maxn, d[i] ^ query(d[i], 30, head));\r\n    cout << maxn << endl;\r\n    return 0;\r\n}', '1 [USACO12DEC] First! G\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nstruct Node { char c; bool vis = false; int son[26] = {0}; };\r\nconst int N = 3e4 + 10;\r\nstring s[N];\r\n\r\nint main() {\r\n    int n;\r\n    cin >> n;\r\n    for (int i = 1; i <= n; i++)\r\n        cin >> s[i];\r\n    vector<Node> v;\r\n    v.push_back({\'*\'});\r\n    for (int i = 1; i <= n; i++) {\r\n        int t = 0;\r\n        for (int j = 0; j < s[i].size(); j++) {\r\n            int tmp = int(s[i][j] - \'a\');\r\n            if (v[t].son[tmp] == 0) {\r\n                v[t].son[tmp] = v.size();\r\n                v.push_back({s[i][j]});\r\n            } t = v[t].son[tmp];\r\n        } v[t].vis = true;\r\n    } vector<int> ans;\r\n    for (int i = 1; i <= n; i++) {\r\n        vector<int> g[26];\r\n        bool flag = true;\r\n        int d[26] = {0};\r\n        int t = 0;\r\n        for (int j = 0; j < s[i].size(); j++) {\r\n            int tmp = int(s[i][j] - \'a\');\r\n            if (v[t].vis)\r\n                flag = false;\r\n            for (int k = 0; k < 26; k++) {\r\n                if (v[t].son[k] == 0 or tmp == k) continue;\r\n                g[tmp].push_back(k);\r\n            } t = v[t].son[tmp];\r\n        } for (int j = 0; j < 26; j++)\r\n            for (int k : g[j])\r\n                d[k]++;\r\n        queue<int> q;\r\n        for (int j = 0; j < 26; j++)\r\n            if (d[j] == 0)\r\n                q.push(j);\r\n        while (!q.empty()) {\r\n            int j = q.front();\r\n            q.pop();\r\n            for (int k : g[j]) {\r\n                d[k]--;\r\n                if (d[k] == 0)\r\n                    q.push(k);\r\n            }\r\n        } for (int j = 0; j < 26; j++)\r\n            if (d[j] != 0)\r\n                flag = false;\r\n        if (flag) ans.push_back(i);\r\n    } cout << ans.size() << endl;\r\n    for (int i : ans)\r\n        cout << s[i] << endl;\r\n    return 0;\r\n}', '1 【模板】AC 自动机\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nstruct Node { char c; int v = 0, f = 0; int son[26] = {0}; };\r\nconst int N = 2e5 + 10;\r\nint id[N], cnt[N], ord[N];\r\nvector<Node> v;\r\nstring t[N];\r\n\r\nvoid init(int& n) {\r\n    v.push_back({\'*\'});\r\n    for (int i = 1; i <= n; i++) {\r\n        int it = 0;\r\n        for (int j = 0; j < t[i].size(); j++) {\r\n            int tmp = int(t[i][j] - \'a\');\r\n            if (v[it].son[tmp] == 0) {\r\n                v[it].son[tmp] = v.size();\r\n                v.push_back({t[i][j]});\r\n            } it = v[it].son[tmp];\r\n        } v[it].v++;\r\n        id[i] = it;\r\n    } queue<int> q;\r\n    for (int i = 0; i < 26; i++) {\r\n        if (v[0].son[i] != 0) {\r\n            v[v[0].son[i]].f = 0;\r\n            q.push(v[0].son[i]);\r\n        }\r\n    } int tmp = 1;\r\n    while (!q.empty()) {\r\n        int u = q.front();\r\n        q.pop();\r\n        ord[u] = tmp++;\r\n        for (int i = 0; i < 26; i++) {\r\n            if (v[u].son[i] != 0) {\r\n                v[v[u].son[i]].f = v[v[u].f].son[i];\r\n                q.push(v[u].son[i]);\r\n            } else v[u].son[i] = v[v[u].f].son[i];\r\n        }\r\n    }\r\n}\r\n\r\nvoid query(string& s) {\r\n    int u = 0;\r\n    for (int i = 0; i < s.size(); i++) {\r\n        int t = int(s[i] - \'a\');\r\n        u = v[u].son[t];\r\n        cnt[u]++;\r\n    }\r\n}\r\n\r\nint main() {\r\n    string s;\r\n    int n;\r\n    cin >> n;\r\n    for (int i = 1; i <= n; i++)\r\n        cin >> t[i];\r\n    init(n);\r\n    cin >> s;\r\n    query(s);\r\n    for (int i = v.size() - 1; i >= 0; i--) \r\n        cnt[v[ord[i]].f] += cnt[ord[i]];\r\n    for (int i = 1; i <= n; i++)\r\n        cout << cnt[id[i]] << endl;\r\n    return 0;\r\n}', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none', 'none');

-- --------------------------------------------------------

--
-- 表的结构 `cookie`
--

CREATE TABLE `cookie` (
  `cookie` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `uid` int(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- 转存表中的数据 `cookie`
--

INSERT INTO `cookie` (`cookie`, `uid`) VALUES
('ec7b04b7d5c2b58d70009ca55f03c7362182d8b2e65c7a7e707a5560e2bbbdef', 1013),
('bee39ec5edc625a5caa6d6622c1a1157d322634cb09e5cb588884bc3e722ebc6', 1011),
('226d0bd32cf9f972b5017a66fd734a827051380d4a0b87d3ac4344fb860c29f0', 1010),
('50556473e2c79a06f54c7b6e576e603278e4b916ca7665389710913e93bbcb99', 2),
('1e1c909f60417fc2f7bfa35aa7214b67f840e9ab2381a20272cddc6e5c780fa8', 1008),
('882653bd4edf34bff8efe01638b66005611932084786cf4bc8b73564be657bfe', 1009),
('38169fdeda49fced2352ff6be1e9912f42fc73e9484f6a783788f94ce02dd55d', 1);

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE `user` (
  `uid` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `calling` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `alc` int(100) NOT NULL,
  `color` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `num` int(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- 转存表中的数据 `user`
--

INSERT INTO `user` (`uid`, `name`, `calling`, `password`, `alc`, `color`, `num`) VALUES
(1, 'Cube', 'qwq', '$2y$10$9Kx0112zqogkZ8NXThiSMOpgv3zp8v6w7es99ZnxpuOWwIpcA77Ky', 2, '#8e44ad', 19),
(2, 'qwerhh', '人机', '$2y$10$Lryj366HuKSa2I5WGGkIaO2q8JNaz1sE/3Y.I5xAbyIXPgLFdqKqK', 2, '#8e44ad', 14),
(12, '17308621787', '自动 AC 机', '$2y$10$ztOO8MkiXanPrC4/Q4BeCeehICcKZdunyeK9blcvOO0h8R7Y860YC', 1, '#fe4c61', 499),
(1007, 'zzc2025', 'none', '$2y$10$fH19IX3RhtrIMa1YG4AeyOc5TsIvPCz0oz8yb6BXupoVMT8uQAG7u', 1, '#0e90d2', 0),
(1006, 'zyx_awa', 'none', '$2y$10$sdTD/a8WKcqlujDx30yuuOBQHMuEO5AM/MLL1kx4Y692EUlNyDxfe', 1, '#0e90d2', 0),
(1005, 'DCEACE', 'none', '$2y$10$2RE7PnEwY09QuuZetyUCYumeFleOkR5Jw/voELom8QIIHF1XD7z1W', 1, '#0e90d2', 0),
(1008, 'x', 'none', '$2y$10$u.piw0WEtwUYBUGkW/NcHOAUyjga783SIjkP7WNVFdYS.twjWYuTy', 1, '#0e90d2', 0),
(1009, 'Yanzher', 'none', '$2y$10$xat8KdyE/.1VLY/r43uyBOAlkW3VGqfUWyFPcvJUziMkkERmk9XVu', 1, '#95a5a6', 5),
(1010, 'elonzhang', 'none', '$2y$10$iVILRF61AB9VwqyzF7TZEOCPJQFCBTVC9mjtmb4haiIOLpNnalHBu', 2, '#0e90d2', 0),
(1011, '114514', 'none', '$2y$10$hey7.3I5diR19TAh0Nn3yulxfCvnRdxPolJg5HER6y8hqXaCum.y6', 1, '#0e90d2', 0),
(1012, 'test001', 'none', '$2y$10$xeYmuH./Zm40sIRF2oruwehb5qyEClCBv9AbBd4Ew97tqr/hnr8W6', 1, '#0e90d2', 0),
(1013, 'zmlhavenoegg', 'none', '$2y$10$hR/wIFRrATLvFzDkelOzYurXsnRtHh8YG47vCB06Qa.xGu7inu1D.', 1, '#0e90d2', 0);

--
-- 转储表的索引
--

--
-- 表的索引 `ai_config`
--
ALTER TABLE `ai_config`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ai_server`
--
ALTER TABLE `ai_server`
  ADD PRIMARY KEY (`uid`);

--
-- 表的索引 `ai_usage`
--
ALTER TABLE `ai_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uid_time` (`uid`,`used_at`);

--
-- 表的索引 `cook`
--
ALTER TABLE `cook`
  ADD PRIMARY KEY (`uid`);

--
-- 表的索引 `cookie`
--
ALTER TABLE `cookie`
  ADD PRIMARY KEY (`cookie`);

--
-- 表的索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`uid`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `ai_usage`
--
ALTER TABLE `ai_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- 使用表AUTO_INCREMENT `user`
--
ALTER TABLE `user`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1014;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
