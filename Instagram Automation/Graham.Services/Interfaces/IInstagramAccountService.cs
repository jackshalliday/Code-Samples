using Graham.DataAccess.Model;
using System.Collections.Generic;

namespace Graham.Services.Interfaces
{
    public interface IInstagramAccountService
    {
        long AddInstagramAccount(InstagramAccount instagramAccount);
        bool FindInstagramAccountById(long instagramAccountId, out InstagramAccount instagramAccount);
        bool FindInstagramAccountByUsername(string instagramUsername, out InstagramAccount instagramAccount);
        void UpdateInstagramAccount(InstagramAccount instagramAccount);
        void RemoveInstagramAccount(InstagramAccount instagramAccount);
        IEnumerable<InstagramAccount> GetInstagramAccounts(int paginationValue);
    }
}
