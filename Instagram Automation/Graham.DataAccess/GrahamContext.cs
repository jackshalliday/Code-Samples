using Graham.DataAccess.Model;
using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.SqlServer.Infrastructure.Internal;
using System.Configuration;

namespace Graham.DataAccess
{
    public class GrahamContext : DbContext
    {
        private readonly string _connectionString;

        public GrahamContext() : base() {

            _connectionString = ConfigurationManager.ConnectionStrings["GrahamContext"].ConnectionString;
        }

        public GrahamContext(DbContextOptions<GrahamContext> options) : base(options) {

            _connectionString = options.FindExtension<SqlServerOptionsExtension>().ConnectionString;
        }

        public DbSet<InstagramAccount> InstagramAccounts { get; set; }

        protected override void OnConfiguring(DbContextOptionsBuilder optionsBuilder)
        {
            optionsBuilder.UseSqlServer(_connectionString);
        }
    }
}
