using Graham.DataAccess;
using Graham.DataAccess.Model;
using Graham.Services.Configuration;
using Graham.Services.Interfaces;
using Microsoft.Extensions.Options;
using System.Collections.Generic;
using System.Linq;

namespace Graham.Services
{
    public class InstagramAccountService : IInstagramAccountService
    {
        private readonly GrahamContext _context;
        private readonly AppSettings _settings;

        public InstagramAccountService(GrahamContext context, IOptions<AppSettings> settings)
        {
            _context = context;
            _settings = settings.Value;
        }

        public long AddInstagramAccount(InstagramAccount instagramAccount)
        {
            _context.InstagramAccounts.Add(instagramAccount);
            _context.SaveChanges();

            return instagramAccount.Id;
        }

        public bool FindInstagramAccountById(long instagramAccountId, out InstagramAccount instagramAccount)
        {
            instagramAccount = _context.InstagramAccounts
                .Where(x => x.Id.Equals(instagramAccountId))
                .FirstOrDefault();

            return instagramAccount != null;
        }

        public bool FindInstagramAccountByUsername(string instagramUsername, out InstagramAccount instagramAccount)
        {
            instagramAccount = _context.InstagramAccounts
                .Where(x => x.Username.Equals(instagramUsername))
                .FirstOrDefault();

            return instagramAccount != null;
        }

        public IEnumerable<InstagramAccount> GetInstagramAccounts(int page)
        {
            var total = _context.InstagramAccounts.Select(x => x).Count();
            var pageSize = _settings.ResultsPerPage;
            var skip = pageSize * (page - 1);

            var canPage = skip < total;

            if (!canPage) //if you can page no further
                return null;

            return _context.InstagramAccounts
                .Select(x => x)
                .Skip(skip)
                .Take(pageSize)
                .ToList();
        }

        public void RemoveInstagramAccount(InstagramAccount instagramAccount)
        {
            //this logic will need to be updated if there are any fk
            _context.InstagramAccounts.Remove(instagramAccount);
            _context.SaveChanges();
        }

        public void UpdateInstagramAccount(InstagramAccount entity)
        {
            if (FindInstagramAccountById(entity.Id, out var instagramAccount))
                instagramAccount = entity;

            _context.SaveChanges();
        }
    }
}
